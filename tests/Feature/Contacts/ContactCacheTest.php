<?php

namespace Tests\Feature\Contacts;

use App\Features\Contacts\Actions\DeleteContact;
use App\Features\Contacts\Actions\GetContactDetails;
use App\Features\Contacts\Actions\PlaceContactCall;
use App\Features\Contacts\Actions\SearchContacts;
use App\Features\Contacts\Actions\UpsertContact;
use App\Features\Contacts\Data\ContactData;
use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Enums\ContactSortField;
use App\Features\Contacts\Enums\SortDirection;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\CallSimulator;
use App\Features\Contacts\Support\ContactCache;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ContactCacheTest extends TestCase
{
    use RefreshDatabase;

    private function queriesDuring(Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function details(Contact $contact): Contact
    {
        return app(GetContactDetails::class)->handle($contact->id);
    }

    /**
     * @return array<int, Contact>
     */
    private function list(ContactSearchCriteria $criteria = new ContactSearchCriteria): array
    {
        return app(SearchContacts::class)->handle($criteria)->items();
    }

    public function test_contact_details_are_served_from_cache_on_repeat_reads(): void
    {
        $contact = Contact::factory()->create();

        $this->assertGreaterThan(0, $this->queriesDuring(fn () => $this->details($contact)));
        $this->assertSame(0, $this->queriesDuring(fn () => $this->details($contact)));

        $cached = $this->details($contact);
        $this->assertSame($contact->id, $cached->id);
        $this->assertTrue($cached->exists);
        $this->assertTrue($cached->relationLoaded('latestCalls'));
    }

    public function test_cached_calls_keep_their_enum_casts(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andReturn(CallOutcome::Busy);
        $contact = Contact::factory()->create();
        app(PlaceContactCall::class)->handle($contact, async: false);

        $this->details($contact);
        $cached = $this->details($contact);

        $this->assertSame(CallStatus::Completed, $cached->latestCalls->first()->status);
        $this->assertSame(CallOutcome::Busy, $cached->latestCalls->first()->outcome);
    }

    public function test_updating_a_contact_invalidates_its_details(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Old']);
        $this->details($contact);

        app(UpsertContact::class)->handle(new ContactData('New', 'Name', 'new@example.com', null), $contact);

        $this->assertSame('New', $this->details($contact)->first_name);
    }

    public function test_placing_a_call_invalidates_the_contacts_latest_calls(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andReturn(CallOutcome::Answered);
        $contact = Contact::factory()->create();
        $this->assertCount(0, $this->details($contact)->latestCalls);

        app(PlaceContactCall::class)->handle($contact, async: false);

        $this->assertCount(1, $this->details($contact)->latestCalls);
    }

    public function test_deleting_a_contact_invalidates_its_details(): void
    {
        $contact = Contact::factory()->create();
        $this->details($contact);

        app(DeleteContact::class)->handle($contact);

        $this->assertFalse(Cache::has(app(ContactCache::class)->detailKey($contact->id)));
    }

    public function test_unfiltered_listing_is_cached_until_a_contact_changes(): void
    {
        Contact::factory()->create(['last_name' => 'Adams']);
        $this->list();

        $this->assertSame(0, $this->queriesDuring(fn () => $this->list()));

        app(UpsertContact::class)->handle(new ContactData('Zoe', 'Baker', 'zoe@example.com', null));

        $this->assertSame(['Adams', 'Baker'], collect($this->list())->pluck('last_name')->all());
    }

    public function test_filtered_searches_are_not_cached(): void
    {
        Contact::factory()->create(['first_name' => 'Jo']);
        $criteria = new ContactSearchCriteria(name: 'jo');
        $this->list($criteria);

        $this->assertSame(2, $this->queriesDuring(fn () => $this->list($criteria)));
    }

    public function test_list_keys_differ_for_every_result_affecting_parameter(): void
    {
        $cache = app(ContactCache::class);
        $base = new ContactSearchCriteria;

        $keys = collect([
            $base,
            new ContactSearchCriteria(sort: ContactSortField::FirstName),
            new ContactSearchCriteria(direction: SortDirection::Desc),
            new ContactSearchCriteria(page: 2),
            new ContactSearchCriteria(perPage: 50),
            new ContactSearchCriteria(name: 'jo'),
            new ContactSearchCriteria(phoneDigits: '61'),
            new ContactSearchCriteria(email: 'example.com'),
        ])->map(fn (ContactSearchCriteria $criteria): string => $cache->listKey($criteria));

        $this->assertCount($keys->count(), $keys->unique());
        $this->assertSame($cache->listKey($base), $cache->listKey(new ContactSearchCriteria));
    }

    public function test_rolled_back_write_does_not_invalidate_the_cache(): void
    {
        $contact = Contact::factory()->create();
        $this->details($contact);
        $this->list();
        $listKey = app(ContactCache::class)->listKey(new ContactSearchCriteria);
        Contact::saved(fn () => throw new RuntimeException('Simulated failure.'));

        try {
            app(UpsertContact::class)->handle(new ContactData('New', 'Name', 'new@example.com', null), $contact);
            $this->fail('Expected the simulated failure.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertTrue(Cache::has(app(ContactCache::class)->detailKey($contact->id)));
        $this->assertSame($listKey, app(ContactCache::class)->listKey(new ContactSearchCriteria));
    }

    public function test_invalidation_waits_for_the_outer_transaction_to_commit(): void
    {
        $contact = Contact::factory()->create();
        $this->details($contact);
        $key = app(ContactCache::class)->detailKey($contact->id);

        DB::transaction(function () use ($contact, $key): void {
            app(UpsertContact::class)->handle(new ContactData('New', 'Name', 'new@example.com', null), $contact);

            $this->assertTrue(Cache::has($key));
        });

        $this->assertFalse(Cache::has($key));
    }

    public function test_cache_can_be_disabled(): void
    {
        config(['contacts.cache.enabled' => false]);
        $contact = Contact::factory()->create();
        $this->details($contact);

        $this->assertGreaterThan(0, $this->queriesDuring(fn () => $this->details($contact)));
    }
}
