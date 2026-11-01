<?php

namespace App\Features\Contacts\Console\Commands;

use App\Features\Contacts\Actions\GetContactDetails;
use App\Features\Contacts\Console\Concerns\InteractsWithContacts;
use App\Features\Contacts\Models\ContactCall;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ShowContactCommand extends Command
{
    use InteractsWithContacts;

    /**
     * @var string
     */
    protected $signature = 'contacts:show {id : Contact UUID}';

    /**
     * @var string
     */
    protected $description = 'Show a contact and its latest 10 calls';

    public function handle(GetContactDetails $getContactDetails): int
    {
        $id = (string) $this->argument('id');

        if ($this->validateContactId($id) === null) {
            return self::INVALID;
        }

        try {
            $contact = $getContactDetails->handle($id);
        } catch (ModelNotFoundException) {
            $this->components->error('Contact not found.');

            return self::FAILURE;
        }

        $timezone = $this->timezoneLabel();

        $this->components->twoColumnDetail('ID', $contact->id);
        $this->components->twoColumnDetail('First name', $contact->first_name);
        $this->components->twoColumnDetail('Last name', $contact->last_name);
        $this->components->twoColumnDetail('Email', $contact->email ?? '-');
        $this->components->twoColumnDetail('Phone', $contact->phone ?? '-');
        $this->components->twoColumnDetail("Created ({$timezone})", $this->localTime($contact->created_at));
        $this->components->twoColumnDetail("Updated ({$timezone})", $this->localTime($contact->updated_at));

        $this->newLine();

        if ($contact->latestCalls->isEmpty()) {
            $this->components->info('No calls yet.');

            return self::SUCCESS;
        }

        $this->table(
            ["Placed ({$timezone})", 'Phone', 'Status', 'Outcome', 'Call ID'],
            $contact->latestCalls->map(fn (ContactCall $call): array => [
                $this->localTime($call->created_at),
                $call->phone,
                $call->status->value,
                $call->outcome->value ?? '-',
                $call->id,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
