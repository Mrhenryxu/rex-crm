<?php

namespace App\Features\Contacts\Support;

use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Models\ContactCall;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Targeted read cache for Contacts.
 *
 * Stores raw attribute arrays, not models: Laravel's cache refuses to unserialize objects
 * (cache.serializable_classes = false). Listing keys sit under a version token, so a write
 * invalidates every cached page at once without enumerating keys or needing cache tags.
 * Invalidation is registered with DB::afterCommit, so a rolled-back write changes nothing.
 */
final class ContactCache
{
    private const string LIST_VERSION_KEY = 'contacts:list:version';

    /**
     * @param  Closure(): Contact  $load
     */
    public function rememberDetail(string $contactId, Closure $load): Contact
    {
        if (! $this->enabled()) {
            return $load();
        }

        /** @var array{contact: array<string, mixed>, calls: list<array<string, mixed>>} $payload */
        $payload = Cache::remember($this->detailKey($contactId), $this->ttl('detail_ttl'), function () use ($load): array {
            $contact = $load();

            return [
                'contact' => $contact->getAttributes(),
                'calls' => $contact->latestCalls->map(fn (ContactCall $call): array => $call->getAttributes())->values()->all(),
            ];
        });

        $contact = (new Contact)->newFromBuilder($payload['contact']);

        return $contact->setRelation('latestCalls', ContactCall::hydrate($payload['calls']));
    }

    /**
     * Only unfiltered listings are cached; filtered searches always hit the database.
     *
     * @param  Closure(): LengthAwarePaginatorContract<int, Contact>  $load
     * @return LengthAwarePaginatorContract<int, Contact>
     */
    public function rememberList(ContactSearchCriteria $criteria, Closure $load): LengthAwarePaginatorContract
    {
        if (! $this->enabled() || $criteria->hasFilters()) {
            return $load();
        }

        /** @var array{items: list<array<string, mixed>>, total: int} $payload */
        $payload = Cache::remember($this->listKey($criteria), $this->ttl('list_ttl'), function () use ($load): array {
            $page = $load();

            return [
                'items' => collect($page->items())->map(fn (Contact $contact): array => $contact->getAttributes())->all(),
                'total' => $page->total(),
            ];
        });

        return new LengthAwarePaginator(
            Contact::hydrate($payload['items']),
            $payload['total'],
            $criteria->perPage,
            $criteria->page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    /**
     * After a contact is created, updated or deleted: drop its detail and every cached listing page.
     */
    public function forgetContactAfterCommit(string $contactId): void
    {
        DB::afterCommit(function () use ($contactId): void {
            Cache::forget($this->detailKey($contactId));
            Cache::forever(self::LIST_VERSION_KEY, Str::random(16));
        });
    }

    /**
     * After a call is created or updated: drop the contact detail, which holds the latest calls.
     */
    public function forgetCallsAfterCommit(string $contactId): void
    {
        DB::afterCommit(fn () => Cache::forget($this->detailKey($contactId)));
    }

    public function detailKey(string $contactId): string
    {
        return "contacts:detail:{$contactId}";
    }

    public function listKey(ContactSearchCriteria $criteria): string
    {
        $version = Cache::rememberForever(self::LIST_VERSION_KEY, fn (): string => Str::random(16));

        // Every value that changes the result is part of the key.
        $parameters = [
            $criteria->name,
            $criteria->phonePrefix,
            $criteria->emailDomain,
            $criteria->sort->value,
            $criteria->direction->value,
            $criteria->page,
            $criteria->perPage,
        ];

        return "contacts:list:{$version}:".hash('sha256', (string) json_encode($parameters));
    }

    private function enabled(): bool
    {
        return (bool) config('contacts.cache.enabled');
    }

    private function ttl(string $key): int
    {
        return (int) config("contacts.cache.{$key}");
    }
}
