<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Models\ContactCall;

/**
 * Marks a still-pending call as failed after processing gave up, so it never stays pending.
 */
final class MarkContactCallFailed
{
    public function handle(string $callId): void
    {
        ContactCall::query()
            ->whereKey($callId)
            ->where('status', CallStatus::Pending)
            ->update(['status' => CallStatus::Failed, 'updated_at' => now()]);
    }
}
