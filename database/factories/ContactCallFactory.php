<?php

namespace Database\Factories;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactCall>
 */
class ContactCallFactory extends Factory
{
    protected $model = ContactCall::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'phone' => '+614'.fake()->numerify('########'),
            'status' => CallStatus::Pending,
            'outcome' => null,
            'completed_at' => null,
        ];
    }

    public function completed(CallOutcome $outcome = CallOutcome::Answered): static
    {
        return $this->state(fn (): array => [
            'status' => CallStatus::Completed,
            'outcome' => $outcome,
            'completed_at' => now(),
        ]);
    }
}
