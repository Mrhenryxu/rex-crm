<?php

namespace App\Features\Contacts\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Another contact already owns the email address.
 */
final class DuplicateContactEmail extends RuntimeException
{
    public static function make(?Throwable $previous = null): self
    {
        return new self('The email address already belongs to another contact.', 409, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['email' => [$this->getMessage()]],
        ], 409);
    }
}
