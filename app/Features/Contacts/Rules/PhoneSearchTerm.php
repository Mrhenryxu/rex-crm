<?php

namespace App\Features\Contacts\Rules;

use App\Features\Contacts\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PhoneSearchTerm implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumber::searchPrefix($value) === null) {
            $fail('The :attribute filter must be the start of an E.164 number: 2 to 15 digits with an optional leading +, e.g. +6141.');
        }
    }
}
