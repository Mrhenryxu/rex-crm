<?php

namespace App\Features\Contacts\Rules;

use App\Features\Contacts\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AuNzPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNumber::isValidAuNz($value)) {
            $fail('The :attribute must be an Australian (+61) or New Zealand (+64) number in E.164 format, e.g. +61412345678.');
        }
    }
}
