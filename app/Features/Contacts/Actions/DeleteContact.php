<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Models\Contact;
use Illuminate\Support\Facades\DB;

final class DeleteContact
{
    /**
     * Delete the contact and its call history in one transaction.
     * The foreign key also cascades; deleting calls explicitly keeps the rule visible here.
     */
    public function handle(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            $contact->calls()->delete();
            $contact->delete();
        });
    }
}
