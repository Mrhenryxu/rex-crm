<?php

namespace Tests\Feature\Contacts\Actions;

use App\Features\Contacts\Actions\GetContactDetails;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetContactDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_contact_with_its_latest_ten_calls(): void
    {
        $contact = Contact::factory()->create();
        foreach (range(1, 12) as $minutesAgo) {
            ContactCall::factory()->for($contact)->create(['created_at' => now()->subMinutes($minutesAgo)]);
        }
        ContactCall::factory()->create();

        $found = app(GetContactDetails::class)->handle($contact->id);

        $this->assertTrue($found->relationLoaded('latestCalls'));
        $this->assertCount(10, $found->latestCalls);
        $this->assertTrue($found->latestCalls->every(fn (ContactCall $call): bool => $call->contact_id === $contact->id));
        $this->assertEquals(
            $found->latestCalls->sortByDesc('created_at')->pluck('id')->all(),
            $found->latestCalls->pluck('id')->all(),
        );
    }

    public function test_unknown_contact_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(GetContactDetails::class)->handle((string) Str::uuid7());
    }
}
