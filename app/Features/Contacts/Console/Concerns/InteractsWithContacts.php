<?php

namespace App\Features\Contacts\Console\Concerns;

use App\Features\Contacts\Models\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Shared input handling for the contacts:* commands. Validation uses the same rule sets as the API.
 *
 * @mixin Command
 */
trait InteractsWithContacts
{
    /**
     * Validate input, printing every error. Returns the validated data, or null on failure.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>|null
     */
    protected function validateInput(array $input, array $rules, array $messages = []): ?array
    {
        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return null;
        }

        return $validator->validated();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function validateContactId(mixed $id): ?array
    {
        return $this->validateInput(['id' => $id], ['id' => ['required', 'uuid']], ['id.uuid' => 'The contact ID must be a valid UUID.']);
    }

    /**
     * Resolve a contact by ID, as route-model binding does for the API.
     */
    protected function findContact(string $id): ?Contact
    {
        $contact = Contact::query()->find($id);

        if ($contact === null) {
            $this->components->error('Contact not found.');
        }

        return $contact;
    }

    /**
     * Trim option values; blank values become null.
     */
    protected function optionValue(string $name): ?string
    {
        $value = $this->option($name);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    protected function localTime(?Carbon $time): string
    {
        return $time?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '-';
    }

    protected function timezoneLabel(): string
    {
        return (string) config('app.timezone');
    }
}
