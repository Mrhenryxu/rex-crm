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

    /**
     * The normalised part after the last "@", or null when there is none.
     */
    public static function domainOf(?string $email): ?string
    {
        $normalised = self::normalise($email);

        if ($normalised === null || ! str_contains($normalised, '@')) {
            return null;
        }

        $domain = substr($normalised, strrpos($normalised, '@') + 1);

        return $domain === '' ? null : $domain;
    }
}
