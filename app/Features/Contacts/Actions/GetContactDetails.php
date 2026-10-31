<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetContactDetails
{
    /**
     * The contact with its latest calls (newest first, limited to Contact::LATEST_CALLS_LIMIT).
     *
     * @throws ModelNotFoundException<Contact>
     */
    public function handle(string $contactId): Contact
    {
        return Contact::query()->with('latestCalls')->findOrFail($contactId);
    }
}
