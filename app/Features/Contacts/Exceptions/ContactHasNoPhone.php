<?php

namespace App\Features\Contacts\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A call was requested for a contact without a phone number.
 */
final class ContactHasNoPhone extends RuntimeException
{
    public static function make(): self
    {
        return new self('The contact has no phone number to call. Add a phone number first.', 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['phone' => [$this->getMessage()]],
        ], 422);
    }
}
