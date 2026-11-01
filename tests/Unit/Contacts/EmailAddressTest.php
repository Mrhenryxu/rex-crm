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
}
