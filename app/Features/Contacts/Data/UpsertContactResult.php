<?php

namespace App\Features\Contacts\Data;

use App\Features\Contacts\Models\Contact;

final readonly class UpsertContactResult
{
    public function __construct(
        public Contact $contact,
        public bool $created,
    ) {}
}
