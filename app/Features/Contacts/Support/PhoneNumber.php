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
     * Digits to look for anywhere in a stored E.164 number, e.g. "+61 412-3" becomes "614123".
     * A local number's leading trunk 0 is dropped, because E.164 omits it: "0412 345" becomes "412345",
     * which matches "+61412345678". Returns null when the term is not 2 to 15 digits.
     */
    public static function searchDigits(string $term): ?string
    {
        $compact = preg_replace('/[\s\-().]/', '', $term) ?? '';

        if (preg_match('/^(\+?)(\d{2,15})$/', $compact, $matches) !== 1) {
            return null;
        }

        return $matches[1] === '' && str_starts_with($matches[2], '0') ? substr($matches[2], 1) : $matches[2];
    }
}
