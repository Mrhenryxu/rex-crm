<?php

namespace App\Features\Contacts\Support;

/**
 * AU/NZ E.164 phone rules, implemented without third-party libraries.
 */
final class PhoneNumber
{
    /** Australia: +61 then geographic (2, 3, 7, 8) or mobile (4) and 8 more digits. */
    private const string AUSTRALIA = '/^\+61[23478]\d{8}$/';

    /** New Zealand: +64 then landline (3, 4, 6, 7, 9) and 7 digits, or mobile (2x) and 7 to 9 digits. */
    private const string NEW_ZEALAND = '/^\+64(?:[34679]\d{7}|2\d{7,9})$/';

    public static function isValidAuNz(string $value): bool
    {
        return preg_match(self::AUSTRALIA, $value) === 1
            || preg_match(self::NEW_ZEALAND, $value) === 1;
    }

    /**
     * Normalise a phone search term to an E.164 prefix, e.g. "+61 412-3" becomes "+614123".
     * Returns null when the term cannot be a prefix of an E.164 number.
     */
    public static function searchPrefix(string $term): ?string
    {
        $compact = preg_replace('/[\s\-().]/', '', $term) ?? '';

        if (preg_match('/^\+?(\d{2,15})$/', $compact, $matches) !== 1) {
            return null;
        }

        return '+'.$matches[1];
    }
}
