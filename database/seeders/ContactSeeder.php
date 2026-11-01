<?php

namespace Database\Seeders;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Local demo data: enough contacts for two-plus listing pages, every contact-method mix,
 * AU and NZ mobiles and landlines, shared email domains for domain search, and calls with
 * every outcome. Skips when contacts already exist, so restarts never duplicate data.
 */
class ContactSeeder extends Seeder
{
    private const array DOMAINS = ['acme.com.au', 'example.co.nz', 'kiwi.example.nz', 'outback.example.com.au', 'example.com'];

    /** AU mobile (4), AU landlines (2, 3, 7, 8), NZ mobile (2x) and NZ landlines (3, 4, 9). */
    private const array PHONE_FORMATS = ['+614########', '+612########', '+613########', '+617########', '+618########', '+6421#######', '+6422########', '+643#######', '+644#######', '+649#######'];

    public function run(): void
    {
        if (Contact::query()->exists()) {
            $this->command?->info('Contacts already exist; skipping demo data.');

            return;
        }

        DB::transaction(function (): void {
            // Fixed names so search and sorting are easy to try by hand.
            $named = [
                ['Jane', 'Citizen', 'jane@acme.com.au', '+61412345678'],
                ['John', 'Smith', 'john.smith@acme.com.au', null],
                ['Aroha', 'Ngata', null, '+64211234567'],
                ['Mere', 'Walker', 'mere@example.co.nz', '+6493012345'],
                ['Liam', 'Nguyen', 'liam@outback.example.com.au', '+61291234567'],
            ];

            foreach ($named as [$first, $last, $email, $phone]) {
                $this->createContact($first, $last, $email, $phone);
            }

            foreach (range(1, 45) as $index) {
                $method = Arr::random(['both', 'both', 'email', 'phone']);

                $this->createContact(
                    fake()->firstName(),
                    fake()->lastName(),
                    $method === 'phone' ? null : "contact{$index}@".Arr::random(self::DOMAINS),
                    $method === 'email' ? null : fake()->numerify(Arr::random(self::PHONE_FORMATS)),
                );
            }
        });

        $this->command?->info(sprintf('Seeded %d contacts and %d calls.', Contact::query()->count(), ContactCall::query()->count()));
    }

    private function createContact(string $firstName, string $lastName, ?string $email, ?string $phone): void
    {
        $createdAt = now()->subMinutes(fake()->numberBetween(60, 60 * 24 * 90));

        $contact = Contact::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if ($phone === null) {
            return;
        }

        for ($calls = fake()->numberBetween(0, 4); $calls > 0; $calls--) {
            $placedAt = fake()->dateTimeBetween($createdAt, 'now');

            ContactCall::factory()->for($contact)->create([
                'phone' => $phone,
                'status' => CallStatus::Completed,
                'outcome' => Arr::random(CallOutcome::cases()),
                'completed_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);
        }
    }
}
