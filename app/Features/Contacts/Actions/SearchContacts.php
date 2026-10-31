<?php

namespace App\Features\Contacts\Actions;

use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Models\Contact;
use App\Features\Contacts\Support\ContactCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters, sorts and paginates contacts in SQL. All client values are bound parameters;
 * sort columns come only from the ContactSortField allow-list.
 */
final class SearchContacts
{
    private const int MAX_NAME_TOKENS = 3;

    public function __construct(private readonly ContactCache $cache) {}

    /**
     * @return LengthAwarePaginator<int, Contact>
     */
    public function handle(ContactSearchCriteria $criteria): LengthAwarePaginator
    {
        return $this->cache->rememberList($criteria, fn (): LengthAwarePaginator => $this->query($criteria));
    }

    /**
     * @return LengthAwarePaginator<int, Contact>
     */
    private function query(ContactSearchCriteria $criteria): LengthAwarePaginator
    {
        // Contains matches. A leading-wildcard LIKE cannot use a btree index, so these filters scan the
        // table; at larger volumes a pg_trgm GIN index would be the fix (needs approval for the extension).
        $query = Contact::query()
            ->when($criteria->name, fn (Builder $query, string $name) => $this->whereNameContains($query, $name))
            ->when($criteria->phoneDigits, fn (Builder $query, string $digits) => $query->where('phone', 'like', '%'.$this->escapeLike($digits).'%'))
            ->when($criteria->email, fn (Builder $query, string $email) => $query->where('email', 'like', '%'.$this->escapeLike($email).'%'));

        foreach ($criteria->sort->columns() as $column) {
            $query->orderBy($column, $criteria->direction->value);
        }

        // Deterministic tie-breaker so rows never move between pages.
        $query->orderBy('id', $criteria->direction->value);

        return $query->paginate(perPage: $criteria->perPage, page: $criteria->page);
    }

    /**
     * Every word must appear somewhere in the first or last name, case-insensitively.
     * "jo smi" and "ohn mit" both match "John Smith".
     *
     * @param  Builder<Contact>  $query
     */
    private function whereNameContains(Builder $query, string $name): void
    {
        $tokens = array_slice(preg_split('/\s+/', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, self::MAX_NAME_TOKENS);

        foreach ($tokens as $token) {
            $pattern = '%'.$this->escapeLike($token).'%';

            $query->where(fn (Builder $query) => $query
                ->whereRaw('lower(first_name) like ?', [$pattern])
                ->orWhereRaw('lower(last_name) like ?', [$pattern]));
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
