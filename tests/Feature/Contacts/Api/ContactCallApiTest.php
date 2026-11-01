<?php

namespace Tests\Feature\Contacts\Api;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Jobs\ProcessContactCallJob;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\CallSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ContactCallApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_async_call_returns_202_with_a_pending_call(): void
    {
        Queue::fake();
        $contact = Contact::factory()->create();

        $this->postJson("/api/v1/contacts/{$contact->id}/calls")
            ->assertAccepted()
            ->assertJsonPath('data.contact_id', $contact->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.outcome', null);

        Queue::assertPushed(ProcessContactCallJob::class);
    }

    public function test_sync_call_returns_201_with_the_outcome(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andReturn(CallOutcome::NoAnswer);
        $contact = Contact::factory()->create(['phone' => '+6491234567']);

        $this->postJson("/api/v1/contacts/{$contact->id}/calls", ['mode' => 'sync'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.outcome', 'no_answer')
            ->assertJsonPath('data.phone', '+6491234567');
    }

    public function test_calling_a_contact_without_a_phone_returns_422(): void
    {
        $contact = Contact::factory()->emailOnly()->create();

        $this->postJson("/api/v1/contacts/{$contact->id}/calls", ['mode' => 'sync'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The contact has no phone number to call. Add a phone number first.')
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_unexpected_failure_returns_a_generic_500_without_internal_details(): void
    {
        config(['app.debug' => false]);
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andThrow(new RuntimeException('SQLSTATE secret detail'));
        $contact = Contact::factory()->create();

        $response = $this->postJson("/api/v1/contacts/{$contact->id}/calls", ['mode' => 'sync'])
            ->assertServerError()
            ->assertExactJson(['message' => 'Server Error']);

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }

    public function test_invalid_mode_returns_422(): void
    {
        $contact = Contact::factory()->create();

        $this->postJson("/api/v1/contacts/{$contact->id}/calls", ['mode' => 'later'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.mode.0', 'The mode must be sync or async.');
    }
}
