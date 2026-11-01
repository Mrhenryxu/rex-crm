<?php

namespace Tests\Feature\Contacts\Actions;

use App\Features\Contacts\Actions\DeleteContact;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeleteContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_the_contact_and_its_calls(): void
    {
        $contact = Contact::factory()->has(ContactCall::factory()->count(3), 'calls')->create();
        $other = ContactCall::factory()->create();

        app(DeleteContact::class)->handle($contact);

        $this->assertModelMissing($contact);
        $this->assertDatabaseCount('contact_calls', 1);
        $this->assertModelExists($other);
    }

    public function test_failure_while_deleting_keeps_the_contact_and_its_calls(): void
    {
        $contact = Contact::factory()->has(ContactCall::factory()->count(2), 'calls')->create();
        Contact::deleting(fn () => throw new RuntimeException('Simulated failure.'));

        try {
            app(DeleteContact::class)->handle($contact);
            $this->fail('Expected the simulated failure.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertModelExists($contact);
        $this->assertDatabaseCount('contact_calls', 2);
    }
}
