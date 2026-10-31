---
name: contacts-actions
description: Reusable Contacts business operations (upsert, delete, read, search, call). Use when implementing or changing an Action class, transaction boundaries, search/filter/sort/pagination queries or cache invalidation.
---

# Contacts Actions

## Purpose

Actions are the only place Contacts business operations run. HTTP controllers, Artisan commands and queue jobs call them and add no business logic of their own.

## Shape

- Namespace `App\Features\Contacts\Actions`. One `final` class per operation with a single public, strongly typed `handle()` method.
- Callers get Actions through constructor injection from the container.
- Input is validated, typed data (see `contacts-validation`), never a `Request` or console input object.
- Return models, paginators or enums. API Resources (`contacts-http-api`) and command output (`contacts-cli`) do the formatting.
- Report domain failures with specific exceptions in `App\Features\Contacts\Exceptions\`. Callers map them to HTTP status codes or exit codes.

## Operations

| Action           | Responsibility                                                                                                                             |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `UpsertContact`  | Create or update one contact. Returns the contact and whether it was created (for 201 vs 200).                                             |
| `DeleteContact`  | Delete one contact by its UUID `id`. Handles call history according to the delete decision in `contacts-domain`.                           |
| `SearchContacts` | Filtered, sorted, paginated query, with optional caching.                                                                                  |
| Read             | Route-model binding by UUID `id` plus the single-contact cache. Add a `FindContact` Action only if HTTP and CLI both need the cached read. |
| Call Actions     | See `contacts-calls-queue`.                                                                                                                |

Do not add Actions the requirements don't need (bulk import, merge, restore).

## Transactions

- Wrap every Action that writes (upsert, delete, call initiation) in `DB::transaction()`.
- If one Action calls another inside a transaction, the outer Action owns the boundary.
- Run cache invalidation and job dispatch only after commit, with `DB::afterCommit()`.
- Never catch and swallow an exception inside the transaction closure.

## Upsert

- Implement exactly one identity strategy once it is decided (see `contacts-domain`). Do not guess.
- Catch `UniqueConstraintViolationException` from concurrent creates and turn it into an informative domain exception.
- Save normalised values: E.164 phone, and email trimmed and lower-cased.

## SearchContacts

The backend owns filtering, searching, sorting and pagination. The frontend only sends parameters and renders the results.

- Filters: name, phone, email domain (from the brief).
- Use query builder bindings only. Never interpolate client input into SQL, including column names. Escape `%` and `_` in `ILIKE` patterns.
- Sort: map the validated `sort` value through an allow-list to a real column, and `direction` to `asc` or `desc`. Always add `id` as the final tie-breaker.
- Pagination: use one style everywhere (`paginate()` or `cursorPaginate()`). Read the default and maximum page sizes from config.
- Eager-load anything the Resource renders, such as the latest call, to avoid N+1 queries.

## Caching

- Single contact: cache by `id`. Forget `contacts:{id}` after commit on every write and delete.
- Search: cache only repeated queries that suit caching, for example the default unfiltered listing and its first pages. Do not cache every unique search.
- Search cache keys must cover every input that changes the result (see the key format in `contacts-domain`).
- Invalidate search results by incrementing `contacts:search:version` after commit on every write.
- Use the native `Cache` API and the configured driver. Measure hit rate and query cost before caching more.

## Testing expectations

- Test each Action directly, without HTTP or CLI.
- Upsert: create path, update path, created flag, normalisation, unique-conflict handling.
- Transactions: force a failure after the first write and assert that no rows were saved, no job was dispatched and nothing was invalidated.
- Search: each filter alone and combined, the sort allow-list, default and maximum page size, stable ordering, and a query-count assertion (no N+1).
- Cache: a write invalidates the single-contact entry and bumps the search version. Different parameters produce different keys.
