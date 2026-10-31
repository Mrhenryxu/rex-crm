<?php

namespace Tests\Feature\Contacts\Actions;

use App\Features\Contacts\Actions\SearchContacts;
use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Enums\ContactSortField;
use App\Features\Contacts\Enums\SortDirection;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     * @return LengthAwarePaginator<int, Contact>
     */
    private function search(array $input = []): LengthAwarePaginator
    {
        $validated = Validator::make($input, ContactRules::search(), ContactRules::searchMessages())->validate();

        return app(SearchContacts::class)->handle(ContactSearchCriteria::fromValidated($validated));
    }

    /**
     * @param  LengthAwarePaginator<int, Contact>  $page
     * @return list<string>
     */
    private function names(LengthAwarePaginator $page): array
    {
        return collect($page->items())->map(fn (Contact $contact): string => "{$contact->first_name} {$contact->last_name}")->values()->all();
    }

    private function seedPeople(): void
    {
        Contact::factory()->create(['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john@acme.com.au', 'phone' => '+61412000001']);
        Contact::factory()->create(['first_name' => 'Joanna', 'last_name' => 'Brown', 'email' => 'jo@example.com', 'phone' => '+61412000002']);
        Contact::factory()->create(['first_name' => 'Aroha', 'last_name' => 'Johnson', 'email' => 'aroha@acme.com.au', 'phone' => '+6421000003']);
        Contact::factory()->create(['first_name' => 'Mere', 'last_name' => 'Ngata', 'email' => null, 'phone' => '+6491234567']);
    }

    public function test_name_filter_is_a_case_insensitive_prefix_on_first_or_last_name(): void
    {
        $this->seedPeople();

        $this->assertSame(['Joanna Brown', 'Aroha Johnson', 'John Smith'], $this->names($this->search(['name' => 'JO'])));
        $this->assertSame(['John Smith'], $this->names($this->search(['name' => 'jo smi'])));
        $this->assertSame([], $this->names($this->search(['name' => 'ohn'])));
    }

    public function test_name_filter_treats_like_wildcards_literally(): void
    {
        $this->seedPeople();

        $this->assertSame([], $this->names($this->search(['name' => '%'])));
        $this->assertSame([], $this->names($this->search(['name' => '_o'])));
    }

    public function test_phone_filter_matches_an_e164_prefix(): void
    {
        $this->seedPeople();

        $this->assertSame(['Joanna Brown', 'John Smith'], $this->names($this->search(['phone' => '+61 412'])));
        $this->assertSame(['Aroha Johnson', 'Mere Ngata'], $this->names($this->search(['phone' => '64'])));
    }

    public function test_email_domain_filter_is_an_exact_case_insensitive_match(): void
    {
        $this->seedPeople();

        $this->assertSame(['Aroha Johnson', 'John Smith'], $this->names($this->search(['email_domain' => '@ACME.com.au'])));
        $this->assertSame([], $this->names($this->search(['email_domain' => 'acme.com'])));
    }

    public function test_filters_combine_with_and(): void
    {
        $this->seedPeople();

        $this->assertSame(['John Smith'], $this->names($this->search(['name' => 'jo', 'email_domain' => 'acme.com.au', 'phone' => '+61'])));
    }

    public function test_default_sort_is_last_name_then_first_name_ascending(): void
    {
        Contact::factory()->create(['first_name' => 'Zed', 'last_name' => 'Adams']);
        Contact::factory()->create(['first_name' => 'Amy', 'last_name' => 'Adams']);
        Contact::factory()->create(['first_name' => 'Bob', 'last_name' => 'Baker']);

        $this->assertSame(['Amy Adams', 'Zed Adams', 'Bob Baker'], $this->names($this->search()));
    }

    public function test_sorts_by_allow_listed_field_and_direction(): void
    {
        Contact::factory()->create(['first_name' => 'Amy', 'last_name' => 'Zulu', 'created_at' => now()->subDays(2)]);
        Contact::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Alpha', 'created_at' => now()->subDay()]);

        $this->assertSame(['Zoe Alpha', 'Amy Zulu'], $this->names($this->search(['sort' => 'first_name', 'direction' => 'desc'])));
        $this->assertSame(['Zoe Alpha', 'Amy Zulu'], $this->names($this->search(['sort' => 'created_at', 'direction' => 'desc'])));
    }

    public function test_equal_sort_values_are_ordered_by_id_for_stable_pages(): void
    {
        $contacts = Contact::factory()->count(5)->create(['first_name' => 'Same', 'last_name' => 'Name']);

        $firstPage = collect($this->search(['per_page' => 3, 'page' => 1])->items())->pluck('id');
        $secondPage = collect($this->search(['per_page' => 3, 'page' => 2])->items())->pluck('id');

        $this->assertSame($contacts->pluck('id')->sort()->values()->all(), $firstPage->merge($secondPage)->all());
    }

    public function test_paginates_with_defaults_and_metadata(): void
    {
        Contact::factory()->count(20)->create();

        $page = $this->search();

        $this->assertSame(15, $page->perPage());
        $this->assertSame(20, $page->total());
        $this->assertSame(2, $page->lastPage());
        $this->assertCount(15, $page->items());
    }

    public function test_page_past_the_end_is_empty_with_correct_metadata(): void
    {
        Contact::factory()->count(3)->create();

        $page = $this->search(['page' => 5, 'per_page' => 2]);

        $this->assertSame([], $page->items());
        $this->assertSame(3, $page->total());
        $this->assertSame(2, $page->lastPage());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidParameters(): array
    {
        return [
            'unknown sort field' => [['sort' => 'email'], 'sort'],
            'sql in sort field' => [['sort' => 'id; drop table contacts'], 'sort'],
            'invalid direction' => [['direction' => 'sideways'], 'direction'],
            'page zero' => [['page' => 0], 'page'],
            'non-numeric page' => [['page' => 'two'], 'page'],
            'per page over maximum' => [['per_page' => 101], 'per_page'],
            'per page zero' => [['per_page' => 0], 'per_page'],
            'malformed phone' => [['phone' => 'abc'], 'phone'],
            'malformed domain' => [['email_domain' => 'not a domain'], 'email_domain'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('invalidParameters')]
    public function test_invalid_query_parameters_are_rejected(array $input, string $field): void
    {
        $errors = Validator::make($input, ContactRules::search(), ContactRules::searchMessages())->errors();

        $this->assertTrue($errors->has($field));
    }

    public function test_maximum_page_size_is_accepted(): void
    {
        Contact::factory()->count(3)->create();

        $this->assertSame(100, $this->search(['per_page' => 100])->perPage());
    }

    public function test_search_runs_one_select_and_one_count_query(): void
    {
        $this->seedPeople();
        DB::enableQueryLog();

        $this->search(['name' => 'jo', 'sort' => ContactSortField::FirstName->value, 'direction' => SortDirection::Desc->value]);

        $this->assertCount(2, DB::getQueryLog());
    }
}
