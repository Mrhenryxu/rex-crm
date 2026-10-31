<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\ContactCache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetContactDetails
{
    public function __construct(private readonly ContactCache $cache) {}

    /**
     * The contact with its latest calls (newest first, limited to Contact::LATEST_CALLS_LIMIT).
     *
     * @throws ModelNotFoundException<Contact>
     */
    public function handle(string $contactId): Contact
    {
        return $this->cache->rememberDetail(
            $contactId,
            fn (): Contact => Contact::query()->with('latestCalls')->findOrFail($contactId),
        );
    }
}
