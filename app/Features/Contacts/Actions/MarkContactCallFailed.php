<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Support\ContactCache;
use Illuminate\Support\Facades\DB;

/**
 * Marks a still-pending call as failed after processing gave up, so it never stays pending.
 */
final class MarkContactCallFailed
{
    public function __construct(private readonly ContactCache $cache) {}

    public function handle(string $callId): void
    {
        DB::transaction(function () use ($callId): void {
            $call = ContactCall::query()->lockForUpdate()->find($callId);

            if ($call === null || ! $call->isPending()) {
                return;
            }

            $call->update(['status' => CallStatus::Failed]);

            $this->cache->forgetCallsAfterCommit($call->contact_id);
        });
    }
}
