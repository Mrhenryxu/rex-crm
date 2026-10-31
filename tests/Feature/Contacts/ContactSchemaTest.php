<?php

namespace Tests\Feature\Contacts;

use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_gets_a_generated_uuid_primary_key(): void
    {
        $contact = Contact::factory()->create();

        $this->assertTrue(Str::isUuid($contact->id));
        $this->assertSame('id', $contact->getRouteKeyName());
    }

    public function test_email_is_normalised_and_domain_is_derived_on_save(): void
    {
        $contact = Contact::factory()->create(['email' => '  Jane@Example.COM ']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'jane@example.com',
            'email_domain' => 'example.com',
        ]);
        $this->assertArrayNotHasKey('email_domain', $contact->toArray());
    }

    public function test_clearing_the_email_clears_the_domain(): void
    {
        $contact = Contact::factory()->create();

        $contact->update(['email' => null]);

        $this->assertNull($contact->fresh()->email_domain);
    }

    public function test_database_rejects_a_contact_without_email_or_phone(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('contacts_email_or_phone_check');

        DB::table('contacts')->insert([
            'id' => (string) Str::uuid7(),
            'first_name' => 'No',
            'last_name' => 'Method',
            'email' => null,
            'phone' => null,
        ]);
    }

    public function test_database_rejects_a_non_e164_phone(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('contacts_phone_e164_check');

        DB::table('contacts')->insert([
            'id' => (string) Str::uuid7(),
            'first_name' => 'Bad',
            'last_name' => 'Phone',
            'phone' => '0412 345 678',
        ]);
    }

    public function test_database_rejects_a_duplicate_normalised_email(): void
    {
        Contact::factory()->create(['email' => 'jane@example.com']);

        $this->expectException(UniqueConstraintViolationException::class);

        Contact::factory()->create(['email' => 'JANE@example.com']);
    }

    public function test_many_contacts_may_have_no_email(): void
    {
        Contact::factory()->phoneOnly()->count(2)->create();

        $this->assertSame(2, Contact::query()->whereNull('email')->count());
    }

    public function test_deleting_a_contact_cascades_to_its_calls(): void
    {
        $call = ContactCall::factory()->create();

        DB::table('contacts')->where('id', $call->contact_id)->delete();

        $this->assertModelMissing($call);
    }

    public function test_latest_calls_are_limited_to_ten_newest_first(): void
    {
        $contact = Contact::factory()->create();

        foreach (range(1, 12) as $minutesAgo) {
            ContactCall::factory()->for($contact)->create(['created_at' => now()->subMinutes($minutesAgo)]);
        }

        $latest = $contact->latestCalls()->get();

        $this->assertCount(10, $latest);
        $this->assertTrue($latest->first()->created_at->greaterThan($latest->last()->created_at));
    }
}
