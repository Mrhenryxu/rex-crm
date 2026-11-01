<?php

namespace App\Features\Contacts\Console\Commands;

use App\Features\Contacts\Actions\UpsertContact;
use App\Features\Contacts\Console\Concerns\InteractsWithContacts;
use App\Features\Contacts\Data\ContactData;
use App\Features\Contacts\Exceptions\DuplicateContactEmail;
use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Console\Command;

final class UpsertContactCommand extends Command
{
    use InteractsWithContacts;

    /**
     * @var string
     */
    protected $signature = 'contacts:upsert
        {--id= : Update this contact (UUID). Without it, an existing email updates that contact, otherwise a contact is created}
        {--first-name= : First name (required)}
        {--last-name= : Last name (required)}
        {--email= : Email address}
        {--phone= : AU/NZ phone number in E.164 format, e.g. +61412345678}';

    /**
     * @var string
     */
    protected $description = 'Create or update a contact (email or phone is required)';

    public function handle(UpsertContact $upsertContact): int
    {
        $contact = null;
        $id = $this->optionValue('id');

        if ($id !== null) {
            if ($this->validateContactId($id) === null) {
                return self::INVALID;
            }

            if (($contact = $this->findContact($id)) === null) {
                return self::FAILURE;
            }
        }

        $validated = $this->validateInput([
            'first_name' => $this->optionValue('first-name'),
            'last_name' => $this->optionValue('last-name'),
            'email' => $this->optionValue('email'),
            'phone' => $this->optionValue('phone'),
        ], ContactRules::contact(), ContactRules::contactMessages());

        if ($validated === null) {
            return self::INVALID;
        }

        try {
            $result = $upsertContact->handle(ContactData::fromValidated($validated), $contact);
        } catch (DuplicateContactEmail $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info($result->created ? 'Contact created.' : 'Contact updated.');
        $this->components->twoColumnDetail('ID', $result->contact->id);
        $this->components->twoColumnDetail('Name', "{$result->contact->first_name} {$result->contact->last_name}");
        $this->components->twoColumnDetail('Email', $result->contact->email ?? '-');
        $this->components->twoColumnDetail('Phone', $result->contact->phone ?? '-');

        return self::SUCCESS;
    }
}
