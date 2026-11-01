<?php

namespace Tests\Feature\Contacts\Calls;

use App\Features\Contacts\Actions\PlaceContactCall;
use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Exceptions\ContactHasNoPhone;
use App\Features\Contacts\Jobs\ProcessContactCallJob;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Support\CallSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PlaceContactCallTest extends TestCase
{
    use RefreshDatabase;

    private function simulatorReturning(CallOutcome $outcome): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andReturn($outcome);
    }

    /**
     * @return array<string, array{CallOutcome}>
     */
    public static function outcomes(): array
    {
        return collect(CallOutcome::cases())->mapWithKeys(fn (CallOutcome $outcome): array => [$outcome->value => [$outcome]])->all();
    }

    #[DataProvider('outcomes')]
    public function test_sync_call_records_each_simulated_outcome(CallOutcome $outcome): void
    {
        $this->simulatorReturning($outcome);
        $contact = Contact::factory()->create(['phone' => '+61412345678']);

        $call = app(PlaceContactCall::class)->handle($contact, async: false);

        $this->assertSame(CallStatus::Completed, $call->status);
        $this->assertSame($outcome, $call->outcome);
        $this->assertNotNull($call->completed_at);
        $this->assertDatabaseHas('contact_calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'phone' => '+61412345678',
            'outcome' => $outcome->value,
        ]);
    }

    public function test_async_call_is_pending_and_queues_the_job(): void
    {
        Queue::fake();
        $contact = Contact::factory()->create();

        $call = app(PlaceContactCall::class)->handle($contact);

        $this->assertSame(CallStatus::Pending, $call->status);
        $this->assertNull($call->outcome);
        Queue::assertPushed(ProcessContactCallJob::class, fn (ProcessContactCallJob $job): bool => $job->callId === $call->id);
    }

    public function test_job_is_queued_after_the_transaction_commits(): void
    {
        // Queue::fake() ignores afterCommit, so use the real database driver.
        config(['queue.default' => 'database']);
        $contact = Contact::factory()->create();

        $call = DB::transaction(function () use ($contact): ContactCall {
            $call = app(PlaceContactCall::class)->handle($contact);

            // Still inside the transaction: a worker must not see the job yet.
            $this->assertDatabaseCount('jobs', 0);

            return $call;
        });

        $this->assertDatabaseCount('jobs', 1);
        $this->assertStringContainsString($call->id, (string) DB::table('jobs')->value('payload'));
    }

    public function test_job_is_not_dispatched_when_the_transaction_rolls_back(): void
    {
        config(['queue.default' => 'database']);
        $contact = Contact::factory()->create();

        // The Action has already "dispatched" when the surrounding transaction fails.
        try {
            DB::transaction(function () use ($contact): void {
                app(PlaceContactCall::class)->handle($contact);

                throw new RuntimeException('Simulated failure after the call was recorded.');
            });
            $this->fail('Expected the simulated failure.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('contact_calls', 0);
    }

    public function test_contact_without_a_phone_cannot_be_called(): void
    {
        Queue::fake();
        $contact = Contact::factory()->emailOnly()->create();

        $this->expectException(ContactHasNoPhone::class);

        try {
            app(PlaceContactCall::class)->handle($contact);
        } finally {
            Queue::assertNothingPushed();
            $this->assertDatabaseCount('contact_calls', 0);
        }
    }

    public function test_sync_processing_failure_marks_the_call_failed(): void
    {
        $this->mock(CallSimulator::class)->shouldReceive('simulate')->andThrow(new RuntimeException('Dialler down.'));
        $contact = Contact::factory()->create();

        try {
            app(PlaceContactCall::class)->handle($contact, async: false);
            $this->fail('Expected the dialler failure.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertDatabaseHas('contact_calls', ['contact_id' => $contact->id, 'status' => CallStatus::Failed->value, 'outcome' => null]);
    }

    public function test_call_snapshots_the_phone_number(): void
    {
        $this->simulatorReturning(CallOutcome::Busy);
        $contact = Contact::factory()->create(['phone' => '+61412345678']);

        $call = app(PlaceContactCall::class)->handle($contact, async: false);
        $contact->update(['phone' => '+6421123456']);

        $this->assertSame('+61412345678', $call->fresh()->phone);
    }
}
