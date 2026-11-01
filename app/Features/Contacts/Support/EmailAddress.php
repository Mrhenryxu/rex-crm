<?php

namespace App\Features\Contacts\Support;

final class EmailAddress
{
    /**
     * Trim and lower-case the whole address. Empty input becomes null.
     */
    public static function normalise(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalised = mb_strtolower(trim($email));

        return $normalised === '' ? null : $normalised;
    }
}
