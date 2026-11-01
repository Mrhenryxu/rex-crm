<?php

namespace Tests\Unit\Contacts;

use App\Features\Contacts\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validNumbers(): array
    {
        return [
            'AU mobile' => ['+61412345678'],
            'AU Sydney landline' => ['+61291234567'],
            'AU Melbourne landline' => ['+61391234567'],
            'AU Brisbane landline' => ['+61731234567'],
            'AU Adelaide landline' => ['+61881234567'],
            'NZ mobile 8 digits' => ['+64211234567'],
            'NZ mobile 7 digits' => ['+6421123456'],
            'NZ mobile 9 digits' => ['+642112345678'],
            'NZ Auckland landline' => ['+6491234567'],
            'NZ Christchurch landline' => ['+6431234567'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'other country code' => ['+14155552671'],
            'UK number' => ['+447911123456'],
            'AU trunk zero kept' => ['+610412345678'],
            'NZ trunk zero kept' => ['+640211234567'],
            'AU too short' => ['+6141234567'],
            'AU too long' => ['+614123456789'],
            'AU special service 1300' => ['+611300123456'],
            'missing plus' => ['61412345678'],
            'local format' => ['0412345678'],
            'spaces' => ['+61 412 345 678'],
            'hyphens' => ['+61-412-345-678'],
            'NZ mobile too long' => ['+6421123456789'],
            'empty' => [''],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_accepts_australian_and_new_zealand_e164_numbers(string $number): void
    {
        $this->assertTrue(PhoneNumber::isValidAuNz($number));
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_numbers_that_are_not_au_or_nz_e164(string $number): void
    {
        $this->assertFalse(PhoneNumber::isValidAuNz($number));
    }

    public function test_search_digits_strip_formatting(): void
    {
        $this->assertSame('614123', PhoneNumber::searchDigits('+61 412-3'));
        $this->assertSame('61412', PhoneNumber::searchDigits('61412'));
        $this->assertSame('345678', PhoneNumber::searchDigits('345 678'));
    }

    public function test_search_digits_drop_a_local_trunk_zero(): void
    {
        $this->assertSame('412345', PhoneNumber::searchDigits('0412 345'));
        $this->assertSame('91234', PhoneNumber::searchDigits('(09) 1234'));
        $this->assertSame('0412', PhoneNumber::searchDigits('+0412'));
    }

    public function test_search_digits_reject_non_numeric_or_out_of_range_terms(): void
    {
        $this->assertNull(PhoneNumber::searchDigits('abc'));
        $this->assertNull(PhoneNumber::searchDigits('6'));
        $this->assertNull(PhoneNumber::searchDigits('6+1'));
        $this->assertNull(PhoneNumber::searchDigits('+1234567890123456'));
    }
}
