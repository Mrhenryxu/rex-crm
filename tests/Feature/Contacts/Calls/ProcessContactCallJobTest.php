<?php

namespace Tests\Feature\Contacts\Calls;

use App\Features\Contacts\Actions\PlaceContactCall;
use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Jobs\ProcessContactCallJob;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Support\CallSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ProcessContactCallJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $callId): void
    {
        app()->call([new ProcessContactCallJob($callId), 'handle']);
    }

    public function test_job_completes_a_pending_call(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->once()->andReturn(CallOutcome::Voicemail);
        $call = ContactCall::factory()->create();

        $this->runJob($call->id);

        $call->refresh();
        $this->assertSame(CallStatus::Completed, $call->status);
        $this->assertSame(CallOutcome::Voicemail, $call->outcome);
    }

    public function test_running_the_job_twice_does_not_change_a_completed_call(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->once()->andReturn(CallOutcome::Answered);
        $call = ContactCall::factory()->create();

        $this->runJob($call->id);
        $completedAt = $call->fresh()->completed_at;
        $this->travel(5)->minutes();
        $this->runJob($call->id);

        $call->refresh();
        $this->assertSame(CallOutcome::Answered, $call->outcome);
        $this->assertEquals($completedAt, $call->completed_at);
    }

    public function test_missing_call_is_a_successful_no_op(): void
    {
        $this->mock(CallSimulator::class)->shouldNotReceive('simulate');

        $this->runJob((string) Str::uuid7());

        $this->assertDatabaseCount('contact_calls', 0);
    }

    public function test_call_deleted_with_its_contact_before_processing_is_a_no_op(): void
    {
        $this->mock(CallSimulator::class)->shouldNotReceive('simulate');
        $call = ContactCall::factory()->create();
        $call->contact->delete();

        $this->runJob($call->id);

        $this->assertModelMissing($call);
    }

    public function test_job_allows_three_attempts_with_backoff(): void
    {
        $job = new ProcessContactCallJob((string) Str::uuid7());

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30], $job->backoff);
    }

    public function test_unexpected_failure_is_rethrown_so_the_queue_retries(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andThrow(new RuntimeException('Dialler down.'));
        $call = ContactCall::factory()->create();

        try {
            $this->runJob($call->id);
            $this->fail('Expected the failure to propagate for a retry.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertSame(CallStatus::Pending, $call->fresh()->status);
    }

    public function test_failed_handler_marks_a_pending_call_failed(): void
    {
        $call = ContactCall::factory()->create();

        (new ProcessContactCallJob($call->id))->failed(new RuntimeException('Gave up.'));

        $this->assertSame(CallStatus::Failed, $call->fresh()->status);
    }

    public function test_failed_handler_does_not_overwrite_a_completed_call(): void
    {
        $call = ContactCall::factory()->completed(CallOutcome::Busy)->create();

        (new ProcessContactCallJob($call->id))->failed(null);

        $this->assertSame(CallStatus::Completed, $call->fresh()->status);
    }

    public function test_async_call_is_completed_by_the_sync_queue_driver_after_commit(): void
    {
        config(['queue.default' => 'sync']);
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->once()->andReturn(CallOutcome::NoAnswer);
        $contact = Contact::factory()->create();

        $call = app(PlaceContactCall::class)->handle($contact);

        $this->assertSame(CallOutcome::NoAnswer, $call->fresh()->outcome);
    }
}
