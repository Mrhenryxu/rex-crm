<?php

namespace App\Features\Contacts\Rules;

use App\Features\Contacts\Enums\ContactSortField;
use App\Features\Contacts\Enums\SortDirection;
use Illuminate\Validation\Rule;

/**
 * The single definition of Contacts input rules, shared by Form Requests and Artisan commands.
 */
final class ContactRules
{
    public const int NAME_MAX_LENGTH = 100;

    public const int EMAIL_MAX_LENGTH = 254;

    /**
     * @return array<string, list<mixed>>
     */
    public static function contact(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'last_name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'email' => ['nullable', 'required_without:phone', 'string', 'email:rfc', 'max:'.self::EMAIL_MAX_LENGTH],
            'phone' => ['nullable', 'required_without:email', 'string', new AuNzPhoneNumber],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function contactMessages(): array
    {
        $message = 'Provide an email address or a phone number. At least one is required.';

        return [
            'email.required_without' => $message,
            'phone.required_without' => $message,
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function search(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'phone' => ['nullable', 'string', 'max:25', new PhoneSearchTerm],
            // Partial match: any part of the address, including a domain such as "acme.com.au".
            'email' => ['nullable', 'string', 'max:'.self::EMAIL_MAX_LENGTH],
            'sort' => ['nullable', Rule::enum(ContactSortField::class)],
            'direction' => ['nullable', Rule::enum(SortDirection::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('contacts.pagination.max_per_page')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function searchMessages(): array
    {
        return [
            'sort.enum' => 'The sort field must be one of: '.implode(', ', array_column(ContactSortField::cases(), 'value')).'.',
            'direction.enum' => 'The direction must be asc or desc.',
        ];
    }
}
