<?php

namespace Tests\Feature\Contacts;

use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use App\Features\Contacts\Rules\ContactRules;
use Database\Seeders\ContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_valid_contacts_with_calls(): void
    {
        $this->seed(ContactSeeder::class);

        $this->assertSame(50, Contact::query()->count());
        $this->assertGreaterThan(0, ContactCall::query()->count());
        $this->assertTrue(Contact::query()->whereNull('email')->exists());
        $this->assertTrue(Contact::query()->whereNull('phone')->exists());

        // Every seeded contact passes the same rules the API applies.
        Contact::query()->each(function (Contact $contact): void {
            $validator = Validator::make($contact->only(['first_name', 'last_name', 'email', 'phone']), ContactRules::contact());
            $this->assertFalse($validator->fails(), "Invalid seed contact: {$validator->errors()->first()}");
        });
    }

    public function test_does_not_duplicate_data_when_run_again(): void
    {
        $this->seed(ContactSeeder::class);
        $this->seed(ContactSeeder::class);

        $this->assertSame(50, Contact::query()->count());
    }
}
