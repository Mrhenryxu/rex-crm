<?php

namespace Tests\Feature\Contacts\Actions;

use App\Features\Contacts\Actions\UpsertContact;
use App\Features\Contacts\Data\ContactData;
use App\Features\Contacts\Data\UpsertContactResult;
use App\Features\Contacts\Exceptions\DuplicateContactEmail;
use App\Features\Contacts\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UpsertContactTest extends TestCase
{
    use RefreshDatabase;

    private function upsert(ContactData $data, ?Contact $contact = null): UpsertContactResult
    {
        return app(UpsertContact::class)->handle($data, $contact);
    }

    public function test_creates_a_contact_with_normalised_values(): void
    {
        $result = $this->upsert(ContactData::fromValidated([
            'first_name' => '  Jane ',
            'last_name' => 'Citizen',
            'email' => ' Jane@Example.COM ',
            'phone' => '+61412345678',
        ]));

        $this->assertTrue($result->created);
        $this->assertDatabaseHas('contacts', [
            'id' => $result->contact->id,
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'phone' => '+61412345678',
        ]);
    }

    public function test_existing_normalised_email_updates_that_contact(): void
    {
        $existing = Contact::factory()->create(['email' => 'jane@example.com', 'first_name' => 'Old']);

        $result = $this->upsert(new ContactData('Jane', 'Citizen', 'jane@example.com', '+6421123456'));

        $this->assertFalse($result->created);
        $this->assertSame($existing->id, $result->contact->id);
        $this->assertSame(1, Contact::query()->count());
        $this->assertDatabaseHas('contacts', ['id' => $existing->id, 'first_name' => 'Jane', 'phone' => '+6421123456']);
    }

    public function test_phone_only_input_always_creates_a_new_contact(): void
    {
        Contact::factory()->phoneOnly()->create(['phone' => '+61412345678']);

        $this->assertTrue($this->upsert(new ContactData('Jane', 'Citizen', null, '+61412345678'))->created);
        $this->assertSame(2, Contact::query()->count());
    }

    public function test_explicit_target_contact_is_replaced(): void
    {
        $contact = Contact::factory()->create();

        $this->assertFalse($this->upsert(new ContactData('New', 'Name', null, '+64211234567'), $contact)->created);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => 'New',
            'email' => null,
            'phone' => '+64211234567',
        ]);
    }

    public function test_updating_to_an_email_owned_by_another_contact_is_a_conflict(): void
    {
        Contact::factory()->create(['email' => 'taken@example.com']);
        $contact = Contact::factory()->create(['email' => 'mine@example.com']);

        try {
            $this->upsert(new ContactData('Jane', 'Citizen', 'TAKEN@example.com', null), $contact);
            $this->fail('Expected a duplicate email conflict.');
        } catch (DuplicateContactEmail $exception) {
            $this->assertSame('The email address already belongs to another contact.', $exception->getMessage());
        }

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => 'mine@example.com']);
    }

    public function test_failure_after_the_write_rolls_the_upsert_back(): void
    {
        Contact::saved(fn () => throw new RuntimeException('Simulated failure after write.'));

        try {
            $this->upsert(new ContactData('Jane', 'Citizen', 'jane@example.com', null));
            $this->fail('Expected the simulated failure.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertDatabaseCount('contacts', 0);
    }
}
