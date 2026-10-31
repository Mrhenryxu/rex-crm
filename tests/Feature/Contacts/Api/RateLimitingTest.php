<?php

namespace Tests\Feature\Contacts\Api;

use App\Features\Contacts\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_limit_returns_429_with_retry_information_then_resets(): void
    {
        config(['contacts.rate_limits.read' => 2]);

        $this->getJson('/api/v1/contacts')->assertOk()->assertHeader('X-RateLimit-Limit', 2)->assertHeader('X-RateLimit-Remaining', 1);
        $this->getJson('/api/v1/contacts')->assertOk();

        $response = $this->getJson('/api/v1/contacts')->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertSame(
            "Too many requests. Please try again in {$response->headers->get('Retry-After')} seconds.",
            $response->json('message'),
        );

        $this->travel(61)->seconds();

        $this->getJson('/api/v1/contacts')->assertOk();
    }

    public function test_write_limit_is_separate_from_the_read_limit(): void
    {
        config(['contacts.rate_limits.write' => 1, 'contacts.rate_limits.read' => 100]);
        $payload = ['first_name' => 'Jane', 'last_name' => 'Citizen', 'email' => 'jane@example.com'];

        $this->postJson('/api/v1/contacts', $payload)->assertCreated();
        $this->postJson('/api/v1/contacts', $payload)->assertTooManyRequests();

        $this->getJson('/api/v1/contacts')->assertOk();
    }

    public function test_call_limit_is_separate_from_the_write_limit(): void
    {
        Queue::fake();
        config(['contacts.rate_limits.calls' => 1, 'contacts.rate_limits.write' => 100]);
        $contact = Contact::factory()->create();

        $this->postJson("/api/v1/contacts/{$contact->id}/calls")->assertAccepted();
        $this->postJson("/api/v1/contacts/{$contact->id}/calls")->assertTooManyRequests();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['first_name' => 'A', 'last_name' => 'B', 'phone' => '+61412345678'])->assertOk();
    }

    public function test_limits_are_tracked_per_client_ip(): void
    {
        config(['contacts.rate_limits.read' => 1]);

        $this->getJson('/api/v1/contacts', ['REMOTE_ADDR' => '203.0.113.1'])->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.1'])->getJson('/api/v1/contacts')->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.2'])->getJson('/api/v1/contacts')->assertOk();
    }
}
