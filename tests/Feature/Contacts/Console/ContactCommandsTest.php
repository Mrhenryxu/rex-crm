<?php

namespace Tests\Feature\Contacts\Console;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Jobs\ProcessContactCallJob;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Support\CallSimulator;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_creates_a_contact(): void
    {
        $this->artisan('contacts:upsert', ['--first-name' => 'Jane', '--last-name' => 'Citizen', '--email' => ' Jane@Example.com '])
            ->expectsOutputToContain('Contact created.')
            ->expectsOutputToContain('jane@example.com')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('contacts', ['email' => 'jane@example.com', 'first_name' => 'Jane']);
    }

    public function test_upsert_with_an_existing_email_updates_that_contact(): void
    {
        $contact = Contact::factory()->create(['email' => 'jane@example.com']);

        $this->artisan('contacts:upsert', ['--first-name' => 'Jane', '--last-name' => 'Updated', '--email' => 'jane@example.com'])
            ->expectsOutputToContain('Contact updated.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'last_name' => 'Updated']);
    }

    public function test_upsert_with_an_id_updates_that_contact(): void
    {
        $contact = Contact::factory()->create();

        $this->artisan('contacts:upsert', ['--id' => $contact->id, '--first-name' => 'New', '--last-name' => 'Name', '--phone' => '+6421123456'])
            ->expectsOutputToContain('Contact updated.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => null, 'phone' => '+6421123456']);
    }

    public function test_upsert_prints_the_same_validation_messages_as_the_api(): void
    {
        $this->artisan('contacts:upsert', ['--first-name' => 'Jane', '--last-name' => 'Citizen'])
            ->expectsOutputToContain('Provide an email address or a phone number. At least one is required.')
            ->assertExitCode(Command::INVALID);

        $this->artisan('contacts:upsert', ['--first-name' => 'Jane', '--last-name' => 'Citizen', '--phone' => '0412345678'])
            ->expectsOutputToContain('The phone must be an Australian (+61) or New Zealand (+64) number in E.164 format')
            ->assertExitCode(Command::INVALID);

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_upsert_reports_an_email_conflict(): void
    {
        Contact::factory()->create(['email' => 'taken@example.com']);
        $contact = Contact::factory()->create();

        $this->artisan('contacts:upsert', ['--id' => $contact->id, '--first-name' => 'A', '--last-name' => 'B', '--email' => 'taken@example.com'])
            ->expectsOutputToContain('The email address already belongs to another contact.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_upsert_with_an_unknown_or_malformed_id_fails(): void
    {
        $this->artisan('contacts:upsert', ['--id' => (string) Str::uuid7(), '--first-name' => 'A', '--last-name' => 'B', '--email' => 'a@b.com'])
            ->expectsOutputToContain('Contact not found.')
            ->assertExitCode(Command::FAILURE);

        $this->artisan('contacts:upsert', ['--id' => 'nope', '--first-name' => 'A', '--last-name' => 'B', '--email' => 'a@b.com'])
            ->expectsOutputToContain('The contact ID must be a valid UUID.')
            ->assertExitCode(Command::INVALID);
    }

    public function test_show_prints_the_contact_and_latest_calls(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Aroha', 'email' => 'aroha@example.co.nz']);
        ContactCall::factory()->for($contact)->completed(CallOutcome::Voicemail)->create();

        $this->artisan('contacts:show', ['id' => $contact->id])
            ->expectsOutputToContain('Aroha')
            ->expectsOutputToContain('aroha@example.co.nz')
            ->expectsOutputToContain('Australia/Brisbane')
            ->expectsOutputToContain('voicemail')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_show_with_an_unknown_or_malformed_id_fails(): void
    {
        $this->artisan('contacts:show', ['id' => (string) Str::uuid7()])
            ->expectsOutputToContain('Contact not found.')
            ->assertExitCode(Command::FAILURE);

        $this->artisan('contacts:show', ['id' => '42'])
            ->expectsOutputToContain('The contact ID must be a valid UUID.')
            ->assertExitCode(Command::INVALID);
    }

    public function test_search_filters_sorts_and_paginates(): void
    {
        Contact::factory()->create(['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john@acme.com']);
        Contact::factory()->create(['first_name' => 'Joan', 'last_name' => 'Adams', 'email' => 'joan@acme.com']);
        Contact::factory()->create(['first_name' => 'Zed', 'last_name' => 'Other', 'email' => 'zed@acme.com']);

        $this->artisan('contacts:search', ['--name' => 'jo', '--email' => 'acme.com', '--sort' => 'first_name', '--per-page' => '1'])
            ->expectsOutputToContain('Joan')
            ->expectsOutputToContain('Page 1 of 2 (2 contacts, 1 per page)')
            ->doesntExpectOutputToContain('Zed')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_search_rejects_invalid_options(): void
    {
        $this->artisan('contacts:search', ['--sort' => 'email', '--per-page' => '1000'])
            ->expectsOutputToContain('The sort field must be one of: first_name, last_name, created_at.')
            ->expectsOutputToContain('per page')
            ->assertExitCode(Command::INVALID);
    }

    public function test_search_reports_no_results(): void
    {
        $this->artisan('contacts:search', ['--name' => 'nobody'])
            ->expectsOutputToContain('No contacts found.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_delete_asks_for_confirmation(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Jane', 'last_name' => 'Citizen']);

        $this->artisan('contacts:delete', ['id' => $contact->id])
            ->expectsConfirmation('Delete Jane Citizen and their call history?', 'no')
            ->expectsOutputToContain('Deletion cancelled.')
            ->assertExitCode(Command::SUCCESS);
        $this->assertModelExists($contact);

        $this->artisan('contacts:delete', ['id' => $contact->id])
            ->expectsConfirmation('Delete Jane Citizen and their call history?', 'yes')
            ->assertExitCode(Command::SUCCESS);
        $this->assertModelMissing($contact);
    }

    public function test_delete_with_force_skips_confirmation(): void
    {
        $contact = Contact::factory()->has(ContactCall::factory(), 'calls')->create();

        $this->artisan('contacts:delete', ['id' => $contact->id, '--force' => true])
            ->expectsOutputToContain('deleted')
            ->assertExitCode(Command::SUCCESS);

        $this->assertModelMissing($contact);
        $this->assertDatabaseCount('contact_calls', 0);
    }

    public function test_delete_without_force_refuses_when_not_interactive(): void
    {
        $contact = Contact::factory()->create();

        $this->artisan('contacts:delete', ['id' => $contact->id, '--no-interaction' => true])
            ->expectsOutputToContain('Pass --force')
            ->assertExitCode(Command::FAILURE);

        $this->assertModelExists($contact);
    }

    public function test_delete_with_an_unknown_id_fails(): void
    {
        $this->artisan('contacts:delete', ['id' => (string) Str::uuid7(), '--force' => true])
            ->expectsOutputToContain('Contact not found.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_call_queues_the_job_by_default(): void
    {
        Queue::fake();
        $contact = Contact::factory()->create();

        $this->artisan('contacts:call', ['id' => $contact->id])
            ->expectsOutputToContain('queued')
            ->assertExitCode(Command::SUCCESS);

        Queue::assertPushed(ProcessContactCallJob::class);
    }

    public function test_call_with_sync_prints_and_stores_the_outcome(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andReturn(CallOutcome::Busy);
        $contact = Contact::factory()->create();

        $this->artisan('contacts:call', ['id' => $contact->id, '--sync' => true])
            ->expectsOutputToContain('completed with outcome: busy')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('contact_calls', ['contact_id' => $contact->id, 'outcome' => 'busy']);
    }

    public function test_call_fails_for_a_contact_without_a_phone(): void
    {
        $contact = Contact::factory()->emailOnly()->create();

        $this->artisan('contacts:call', ['id' => $contact->id, '--sync' => true])
            ->expectsOutputToContain('The contact has no phone number to call.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_call_with_a_malformed_id_is_invalid(): void
    {
        $this->artisan('contacts:call', ['id' => 'abc'])
            ->expectsOutputToContain('The contact ID must be a valid UUID.')
            ->assertExitCode(Command::INVALID);
    }
}
