<?php

namespace Tests\Feature\Contacts;

use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactValidationTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    private function errorsFor(array $input): array
    {
        return Validator::make($input, ContactRules::contact(), ContactRules::contactMessages())
            ->errors()
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Citizen',
            'email' => null,
            'phone' => null,
            ...$overrides,
        ];
    }

    public function test_contact_requires_email_or_phone(): void
    {
        $errors = $this->errorsFor($this->input());

        $this->assertSame(['Provide an email address or a phone number. At least one is required.'], $errors['email']);
        $this->assertSame(['Provide an email address or a phone number. At least one is required.'], $errors['phone']);
    }

    public function test_email_only_contact_is_valid(): void
    {
        $this->assertSame([], $this->errorsFor($this->input(['email' => 'jane@example.com'])));
    }

    public function test_phone_only_contact_is_valid(): void
    {
        $this->assertSame([], $this->errorsFor($this->input(['phone' => '+61412345678'])));
    }

    public function test_contact_with_both_valid_methods_is_valid(): void
    {
        $this->assertSame([], $this->errorsFor($this->input(['email' => 'jane@example.com', 'phone' => '+6421123456'])));
    }

    public function test_both_methods_are_validated_when_both_are_supplied(): void
    {
        $errors = $this->errorsFor($this->input(['email' => 'jane@example.com', 'phone' => '+14155552671']));

        $this->assertArrayNotHasKey('email', $errors);
        $this->assertSame(
            ['The phone must be an Australian (+61) or New Zealand (+64) number in E.164 format, e.g. +61412345678.'],
            $errors['phone'],
        );

        $errors = $this->errorsFor($this->input(['email' => 'not-an-email', 'phone' => '+61412345678']));

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayNotHasKey('phone', $errors);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidNames(): array
    {
        return [
            'missing first name' => [['first_name' => null], 'first_name'],
            'blank first name' => [['first_name' => '   '], 'first_name'],
            'missing last name' => [['last_name' => null], 'last_name'],
            'first name too long' => [['first_name' => str_repeat('a', 101)], 'first_name'],
            'last name too long' => [['last_name' => str_repeat('a', 101)], 'last_name'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidNames')]
    public function test_first_and_last_name_are_required_with_a_maximum_length(array $overrides, string $field): void
    {
        $errors = $this->errorsFor($this->input(['email' => 'jane@example.com', ...$overrides]));

        $this->assertArrayHasKey($field, $errors);
    }

    public function test_email_longer_than_254_characters_is_rejected(): void
    {
        $errors = $this->errorsFor($this->input(['email' => str_repeat('a', 243).'@example.com']));

        $this->assertArrayHasKey('email', $errors);
    }
}
