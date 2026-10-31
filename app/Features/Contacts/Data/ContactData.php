<?php

namespace App\Features\Contacts\Data;

use App\Features\Contacts\Support\EmailAddress;

/**
 * Validated, normalised contact input shared by HTTP and CLI.
 */
final readonly class ContactData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $email,
        public ?string $phone,
    ) {}

    /**
     * @param  array<string, mixed>  $validated  Output of ContactRules::contact() validation.
     */
    public static function fromValidated(array $validated): self
    {
        $phone = isset($validated['phone']) ? trim((string) $validated['phone']) : '';

        return new self(
            firstName: trim((string) $validated['first_name']),
            lastName: trim((string) $validated['last_name']),
            email: EmailAddress::normalise(isset($validated['email']) ? (string) $validated['email'] : null),
            phone: $phone === '' ? null : $phone,
        );
    }

    /**
     * @return array{first_name: string, last_name: string, email: string|null, phone: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
