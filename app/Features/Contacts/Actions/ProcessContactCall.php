<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Support\CallSimulator;
use Illuminate\Support\Facades\DB;

/**
 * Completes a pending call with a simulated outcome. Used by the sync path and the queue job.
 *
 * Idempotent: a call that is no longer pending is returned unchanged, and a missing call
 * (for example, deleted with its contact) is a no-op that returns null.
 */
final class ProcessContactCall
{
    public function __construct(private readonly CallSimulator $simulator) {}

    public function handle(string $callId): ?ContactCall
    {
        return DB::transaction(function () use ($callId): ?ContactCall {
            // Row lock so concurrent deliveries of the same job cannot both complete the call.
            $call = ContactCall::query()->lockForUpdate()->find($callId);

            if ($call === null || ! $call->isPending()) {
                return $call;
            }

            $call->fill([
                'status' => CallStatus::Completed,
                'outcome' => $this->simulator->simulate($call->phone),
                'completed_at' => now(),
            ])->save();

            return $call;
        });
    }
}
