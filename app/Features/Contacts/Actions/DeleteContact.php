<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\ContactCache;
use Illuminate\Support\Facades\DB;

final class DeleteContact
{
    public function __construct(private readonly ContactCache $cache) {}

    /**
     * Delete the contact and its call history in one transaction.
     * The foreign key also cascades; deleting calls explicitly keeps the rule visible here.
     */
    public function handle(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            $contact->calls()->delete();
            $contact->delete();

            $this->cache->forgetContactAfterCommit($contact->id);
        });
    }
}
