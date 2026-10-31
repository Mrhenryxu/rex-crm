<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Exceptions\ContactHasNoPhone;
use App\Features\Contacts\Jobs\ProcessContactCallJob;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Places a mock call: records a pending call, then completes it now (sync) or on the queue (async).
 * HTTP and CLI share this Action; the queue job shares ProcessContactCall.
 */
final class PlaceContactCall
{
    public function __construct(
        private readonly ProcessContactCall $processContactCall,
        private readonly MarkContactCallFailed $markContactCallFailed,
    ) {}

    /**
     * @throws ContactHasNoPhone
     */
    public function handle(Contact $contact, bool $async = true): ContactCall
    {
        $call = DB::transaction(function () use ($contact, $async): ContactCall {
            if ($contact->phone === null) {
                throw ContactHasNoPhone::make();
            }

            $call = $contact->calls()->create([
                'phone' => $contact->phone,
                'status' => CallStatus::Pending,
            ]);

            if ($async) {
                // Dispatched only if the call row commits; a rolled-back call never reaches a worker.
                ProcessContactCallJob::dispatch($call->id)->afterCommit();
            }

            return $call;
        });

        if ($async) {
            return $call;
        }

        try {
            return $this->processContactCall->handle($call->id) ?? $call;
        } catch (Throwable $exception) {
            $this->markContactCallFailed->handle($call->id);

            throw $exception;
        }
    }
}
