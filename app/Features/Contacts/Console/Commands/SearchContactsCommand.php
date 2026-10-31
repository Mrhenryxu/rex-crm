<?php

namespace App\Features\Contacts\Console\Commands;

use App\Features\Contacts\Actions\SearchContacts;
use App\Features\Contacts\Console\Concerns\InteractsWithContacts;
use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Console\Command;

final class SearchContactsCommand extends Command
{
    use InteractsWithContacts;

    /**
     * @var string
     */
    protected $signature = 'contacts:search
        {--name= : First or last name prefix; every word must match}
        {--phone= : E.164 phone prefix, e.g. +61412}
        {--email-domain= : Exact email domain, e.g. example.com}
        {--sort= : first_name, last_name (default) or created_at}
        {--direction= : asc (default) or desc}
        {--page= : Page number (default 1)}
        {--per-page= : Results per page (default 15, maximum 100)}';

    /**
     * @var string
     */
    protected $description = 'Search contacts by name, phone or email domain';

    public function handle(SearchContacts $searchContacts): int
    {
        $validated = $this->validateInput([
            'name' => $this->optionValue('name'),
            'phone' => $this->optionValue('phone'),
            'email_domain' => $this->optionValue('email-domain'),
            'sort' => $this->optionValue('sort'),
            'direction' => $this->optionValue('direction'),
            'page' => $this->optionValue('page'),
            'per_page' => $this->optionValue('per-page'),
        ], ContactRules::search(), ContactRules::searchMessages());

        if ($validated === null) {
            return self::INVALID;
        }

        $page = $searchContacts->handle(ContactSearchCriteria::fromValidated($validated));

        if ($page->total() === 0) {
            $this->components->info('No contacts found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'First name', 'Last name', 'Email', 'Phone', "Created ({$this->timezoneLabel()})"],
            collect($page->items())->map(fn (Contact $contact): array => [
                $contact->id,
                $contact->first_name,
                $contact->last_name,
                $contact->email ?? '-',
                $contact->phone ?? '-',
                $this->localTime($contact->created_at),
            ])->all(),
        );

        $this->line(sprintf('Page %d of %d (%d contacts, %d per page)', $page->currentPage(), $page->lastPage(), $page->total(), $page->perPage()));

        return self::SUCCESS;
    }
}
