<?php

namespace App\Features\Contacts\Jobs;

use App\Features\Contacts\Actions\MarkContactCallFailed;
use App\Features\Contacts\Actions\ProcessContactCall;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Asynchronous entry point for completing a call. Business logic lives in ProcessContactCall.
 *
 * Carries the call UUID rather than the model, so a call deleted before the job runs is a
 * successful no-op instead of a ModelNotFoundException.
 */
final class ProcessContactCallJob implements ShouldQueue
{
    use Queueable;

    /** Maximum attempts for unexpected failures. Simulated outcomes are results, never retried. */
    public int $tries = 3;

    /** @var list<int> Seconds to wait before the second and third attempts. */
    public array $backoff = [10, 30];

    public function __construct(public readonly string $callId) {}

    public function handle(ProcessContactCall $processContactCall): void
    {
        $processContactCall->handle($this->callId);
    }

    public function failed(?Throwable $exception): void
    {
        app(MarkContactCallFailed::class)->handle($this->callId);
    }
}
