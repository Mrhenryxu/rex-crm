<?php

namespace Database\Factories;

use App\Features\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => self::australianMobile(),
        ];
    }

    public function emailOnly(): static
    {
        return $this->state(fn (): array => ['phone' => null]);
    }

    public function phoneOnly(): static
    {
        return $this->state(fn (): array => ['email' => null]);
    }

    public function australianPhone(): static
    {
        return $this->state(fn (): array => ['phone' => self::australianMobile()]);
    }

    public function newZealandPhone(): static
    {
        return $this->state(fn (): array => ['phone' => '+6421'.fake()->numerify('#######')]);
    }

    private static function australianMobile(): string
    {
        return '+614'.fake()->numerify('########');
    }
}
