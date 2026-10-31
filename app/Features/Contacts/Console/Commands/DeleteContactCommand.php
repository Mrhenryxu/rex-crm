<?php

namespace App\Features\Contacts\Console\Commands;

use App\Features\Contacts\Actions\DeleteContact;
use App\Features\Contacts\Console\Concerns\InteractsWithContacts;
use Illuminate\Console\Command;

final class DeleteContactCommand extends Command
{
    use InteractsWithContacts;

    /**
     * @var string
     */
    protected $signature = 'contacts:delete
        {id : Contact UUID}
        {--force : Delete without asking for confirmation}';

    /**
     * @var string
     */
    protected $description = 'Delete a contact and its call history';

    public function handle(DeleteContact $deleteContact): int
    {
        $id = (string) $this->argument('id');

        if ($this->validateContactId($id) === null) {
            return self::INVALID;
        }

        if (($contact = $this->findContact($id)) === null) {
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->components->error('Refusing to delete without confirmation. Pass --force to delete non-interactively.');

                return self::FAILURE;
            }

            if (! $this->confirm("Delete {$contact->first_name} {$contact->last_name} and their call history?")) {
                $this->components->info('Deletion cancelled.');

                return self::SUCCESS;
            }
        }

        $deleteContact->handle($contact);

        $this->components->info("Contact {$contact->id} deleted.");

        return self::SUCCESS;
    }
}
