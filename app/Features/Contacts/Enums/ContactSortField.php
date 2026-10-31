<?php

namespace App\Features\Contacts\Enums;

/**
 * Allow-list of sortable fields. Client input never reaches SQL as an identifier;
 * it is mapped to these fixed column lists.
 */
enum ContactSortField: string
{
    case FirstName = 'first_name';
    case LastName = 'last_name';
    case CreatedAt = 'created_at';

    /**
     * Columns to order by, before the `id` tie-breaker.
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return match ($this) {
            self::FirstName => ['first_name', 'last_name'],
            self::LastName => ['last_name', 'first_name'],
            self::CreatedAt => ['created_at'],
        };
    }
}
