<?php

namespace App\Features\Contacts\Console\Commands;

use App\Features\Contacts\Actions\PlaceContactCall;
use App\Features\Contacts\Console\Concerns\InteractsWithContacts;
use App\Features\Contacts\Exceptions\ContactHasNoPhone;
use Illuminate\Console\Command;

final class CallContactCommand extends Command
{
    use InteractsWithContacts;

    /**
     * @var string
     */
    protected $signature = 'contacts:call
        {id : Contact UUID}
        {--sync : Place the call now and print the outcome instead of queueing it}';

    /**
     * @var string
     */
    protected $description = 'Place a mock phone call to a contact';

    public function handle(PlaceContactCall $placeContactCall): int
    {
        $id = (string) $this->argument('id');

        if ($this->validateContactId($id) === null) {
            return self::INVALID;
        }

        if (($contact = $this->findContact($id)) === null) {
            return self::FAILURE;
        }

        $sync = (bool) $this->option('sync');

        try {
            $call = $placeContactCall->handle($contact, async: ! $sync);
        } catch (ContactHasNoPhone $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($sync) {
            $this->components->info("Call to {$call->phone} completed with outcome: {$call->outcome?->value}.");
        } else {
            $this->components->info("Call to {$call->phone} queued. A queue worker will record the outcome.");
        }

        $this->components->twoColumnDetail('Call ID', $call->id);

        return self::SUCCESS;
    }
}
