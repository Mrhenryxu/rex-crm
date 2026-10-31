<?php

namespace App\Features\Contacts\Rules;

use App\Features\Contacts\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PhoneSearchTerm implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumber::searchDigits($value) === null) {
            $fail('The :attribute filter must be 2 to 15 digits, optionally with a leading + and spaces, e.g. 0412 345 or +6421.');
        }
    }
}
