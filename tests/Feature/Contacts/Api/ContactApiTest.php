<?php

namespace Tests\Feature\Contacts\Api;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private const array CONTACT_SHAPE = ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'updated_at'];

    public function test_lists_contacts_with_pagination_metadata(): void
    {
        Contact::factory()->count(3)->create();

        $this->getJson('/api/v1/contacts?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [self::CONTACT_SHAPE],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
            ])
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonMissingPath('data.0.latest_calls');
    }

    public function test_listing_applies_combined_filters_and_sort_in_the_query_string(): void
    {
        Contact::factory()->create(['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john@acme.com']);
        Contact::factory()->create(['first_name' => 'Joan', 'last_name' => 'Adams', 'email' => 'joan@acme.com']);
        Contact::factory()->create(['first_name' => 'Jo', 'last_name' => 'Other', 'email' => 'jo@other.com']);

        $response = $this->getJson('/api/v1/contacts?name=jo&email=acme.com&sort=last_name&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.*.first_name', ['John', 'Joan']);

        $this->assertStringContainsString('email=acme.com', (string) $response->json('links.first'));
    }

    public function test_listing_rejects_invalid_query_parameters_with_field_errors(): void
    {
        $this->getJson('/api/v1/contacts?sort=password&direction=up&per_page=500&page=0&phone=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'direction', 'per_page', 'page', 'phone'])
            ->assertJsonPath('errors.sort.0', 'The sort field must be one of: first_name, last_name, created_at.');
    }

    public function test_listing_runs_a_fixed_number_of_queries(): void
    {
        Contact::factory()->has(ContactCall::factory()->count(2), 'calls')->count(10)->create();
        DB::enableQueryLog();

        $this->getJson('/api/v1/contacts')->assertOk();

        $contactQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'contact'));
        $this->assertCount(2, $contactQueries);
    }

    public function test_creates_a_contact_and_returns_201_with_location(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => ' Jane ',
            'last_name' => 'Citizen',
            'email' => 'Jane@Example.com',
            'phone' => '+61412345678',
        ]);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', fn (AssertableJson $data) => $data
                    ->whereType('id', 'string')
                    ->where('first_name', 'Jane')
                    ->where('email', 'jane@example.com')
                    ->where('phone', '+61412345678')
                    ->where('created_at', fn (string $value): bool => str_ends_with($value, 'Z'))
                    ->etc()));

        $id = $response->json('data.id');
        $this->assertTrue(Str::isUuid($id));
        $response->assertHeader('Location', route('contacts.show', $id));
    }

    public function test_posting_an_existing_email_updates_and_returns_200(): void
    {
        $contact = Contact::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/contacts', ['first_name' => 'Jane', 'last_name' => 'Updated', 'email' => 'JANE@example.com'])
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.last_name', 'Updated');

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_create_rejects_a_contact_without_email_or_phone(): void
    {
        $this->postJson('/api/v1/contacts', ['first_name' => 'Jane', 'last_name' => 'Citizen'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone'])
            ->assertJsonPath('errors.email.0', 'Provide an email address or a phone number. At least one is required.');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_create_rejects_an_invalid_phone_sent_directly(): void
    {
        $this->postJson('/api/v1/contacts', ['first_name' => 'Jane', 'last_name' => 'Citizen', 'phone' => '0412 345 678'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'The phone must be an Australian (+61) or New Zealand (+64) number in E.164 format, e.g. +61412345678.');
    }

    public function test_create_rejects_missing_names_and_invalid_email(): void
    {
        $this->postJson('/api/v1/contacts', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }

    public function test_shows_a_contact_with_its_latest_ten_calls(): void
    {
        $contact = Contact::factory()->create();
        foreach (range(1, 11) as $minutesAgo) {
            ContactCall::factory()->for($contact)->completed(CallOutcome::Busy)->create(['created_at' => now()->subMinutes($minutesAgo)]);
        }

        $this->getJson("/api/v1/contacts/{$contact->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonCount(10, 'data.latest_calls')
            ->assertJsonStructure(['data' => [...self::CONTACT_SHAPE, 'latest_calls' => [['id', 'contact_id', 'phone', 'status', 'outcome', 'completed_at', 'created_at']]]])
            ->assertJsonPath('data.latest_calls.0.outcome', 'busy');
    }

    public function test_unknown_contact_returns_404_without_internal_details(): void
    {
        $id = (string) Str::uuid7();

        foreach (['getJson', 'putJson', 'deleteJson', 'postJson'] as $method) {
            $uri = $method === 'postJson' ? "/api/v1/contacts/{$id}/calls" : "/api/v1/contacts/{$id}";
            $payload = $method === 'putJson' ? ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com'] : [];

            $this->{$method}($uri, $payload)
                ->assertNotFound()
                ->assertExactJson(['message' => 'Contact not found.']);
        }
    }

    public function test_malformed_contact_id_returns_404(): void
    {
        $this->getJson('/api/v1/contacts/123')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested resource was not found.']);
    }

    public function test_updates_a_contact_by_id(): void
    {
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['first_name' => 'New', 'last_name' => 'Name', 'phone' => '+6421123456'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.phone', '+6421123456');
    }

    public function test_update_cannot_remove_both_contact_methods(): void
    {
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['first_name' => 'New', 'last_name' => 'Name', 'email' => null, 'phone' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_update_to_another_contacts_email_returns_409(): void
    {
        Contact::factory()->create(['email' => 'taken@example.com']);
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@example.com'])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'The email address already belongs to another contact.',
                'errors' => ['email' => ['The email address already belongs to another contact.']],
            ]);
    }

    public function test_deletes_a_contact_and_its_calls(): void
    {
        $contact = Contact::factory()->has(ContactCall::factory()->count(2), 'calls')->create();

        $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertNoContent();

        $this->assertModelMissing($contact);
        $this->assertDatabaseCount('contact_calls', 0);
    }

    public function test_unsupported_method_returns_json_405(): void
    {
        $this->patchJson('/api/v1/contacts')
            ->assertMethodNotAllowed()
            ->assertJsonStructure(['message']);
    }
}
