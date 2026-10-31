<?php

namespace Tests\Unit\Contacts;

use App\Features\Contacts\Support\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function test_email_is_trimmed_and_lower_cased(): void
    {
        $this->assertSame('jane.doe@example.com', EmailAddress::normalise('  Jane.Doe@Example.COM '));
    }

    public function test_empty_email_normalises_to_null(): void
    {
        $this->assertNull(EmailAddress::normalise('   '));
        $this->assertNull(EmailAddress::normalise(null));
    }

    public function test_domain_is_the_normalised_part_after_the_at_sign(): void
    {
        $this->assertSame('mail.example.co.nz', EmailAddress::domainOf('Kiri@Mail.Example.co.NZ'));
        $this->assertNull(EmailAddress::domainOf('no-at-sign'));
        $this->assertNull(EmailAddress::domainOf(null));
    }
}
