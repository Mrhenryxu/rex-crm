<?php

namespace App\Features\Contacts\Data;

use App\Features\Contacts\Enums\ContactSortField;
use App\Features\Contacts\Enums\SortDirection;
use App\Features\Contacts\Support\EmailAddress;
use App\Features\Contacts\Support\PhoneNumber;

/**
 * Validated search, sort and pagination input with defaults applied.
 * Every filter is a partial (contains) match.
 */
final readonly class ContactSearchCriteria
{
    public function __construct(
        public ?string $name = null,
        public ?string $phoneDigits = null,
        public ?string $email = null,
        public ContactSortField $sort = ContactSortField::LastName,
        public SortDirection $direction = SortDirection::Asc,
        public int $page = 1,
        public int $perPage = 15,
    ) {}

    /**
     * @param  array<string, mixed>  $validated  Output of ContactRules::search() validation.
     */
    public static function fromValidated(array $validated): self
    {
        $name = trim((string) ($validated['name'] ?? ''));
        $phone = trim((string) ($validated['phone'] ?? ''));

        return new self(
            name: $name === '' ? null : $name,
            phoneDigits: $phone === '' ? null : PhoneNumber::searchDigits($phone),
            email: EmailAddress::normalise(isset($validated['email']) ? (string) $validated['email'] : null),
            sort: isset($validated['sort']) ? ContactSortField::from((string) $validated['sort']) : ContactSortField::LastName,
            direction: isset($validated['direction']) ? SortDirection::from((string) $validated['direction']) : SortDirection::Asc,
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? config('contacts.pagination.default_per_page')),
        );
    }

    public function hasFilters(): bool
    {
        return $this->name !== null || $this->phoneDigits !== null || $this->email !== null;
    }
}
