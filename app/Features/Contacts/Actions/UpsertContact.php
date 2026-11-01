<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Data\ContactData;
use App\Features\Contacts\Data\UpsertContactResult;
use App\Features\Contacts\Exceptions\DuplicateContactEmail;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\ContactCache;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a contact.
 *
 * - With a target contact (update by UUID): that contact is replaced with the given data.
 * - Without a target: the normalised email is the upsert key. A matching contact is updated,
 *   otherwise a new one is created. Phone-only input always creates a new contact.
 */
final class UpsertContact
{
    public function __construct(private readonly ContactCache $cache) {}

    public function handle(ContactData $data, ?Contact $contact = null): UpsertContactResult
    {
        try {
            return DB::transaction(function () use ($data, $contact): UpsertContactResult {
                $attributes = $data->toAttributes();

                if ($contact !== null) {
                    $contact->fill($attributes)->save();
                    $created = false;
                } else {
                    $contact = $data->email !== null
                        // Handles a concurrent insert of the same email by re-reading the winner.
                        ? Contact::query()->updateOrCreate(['email' => $data->email], $attributes)
                        : Contact::query()->create($attributes);
                    $created = $contact->wasRecentlyCreated;
                }

                $this->cache->forgetContactAfterCommit($contact->id);

                return new UpsertContactResult($contact, $created);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw DuplicateContactEmail::make($exception);
        }
    }
}
