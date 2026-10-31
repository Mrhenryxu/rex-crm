---
name: contacts-domain
description: Contacts data model, schema, identifiers, indexes and caching. Use when creating or changing Contact or ContactCall models, migrations, factories, enums, relationships, the UUID primary key strategy, search indexes or cache keys.
---

# Contacts Domain

## Purpose

Defines what a Contact and a ContactCall are, and how they are stored. Validation rules live in `contacts-validation`. Operations, queries and cache invalidation live in `contacts-actions`.

## Source of requirements

- Original brief (authoritative): store and retrieve contacts with names, phones and emails. Contacts can be upserted, deleted, searched (name, phone, email domain), read and called (mocked, with possible outcomes).
- Approved plan: required first and last name, optional email and phone with at least one present, UUID identifiers, backend-driven querying, query-driven indexes, targeted caching.

## Identifier strategy (decided)

UUID primary keys for the Contacts feature. There are no auto-increment IDs on Contacts tables.

- `contacts.id` and `contact_calls.id`: `$table->uuid('id')->primary()`. The models use the `HasUuids` trait, which generates time-ordered UUIDv7 values, so inserts stay index-friendly and `id` works as a sort tie-breaker.
- Foreign keys are UUIDs too: `$table->foreignUuid('contact_id')->constrained()`.
- `id` is the identifier used everywhere: routes (default route-model binding), API payloads, CLI arguments and frontend URLs. There is no second public ID column.
- Leave the default `users` table and its bigint key unchanged. Authentication is out of scope.
- This is a recorded, deliberate decision. Never mix identifier strategies within the feature. Migrations, models, factories, route binding, tests and docs must all agree.

A UUID makes enumeration harder. It does not replace authentication, authorisation or ownership checks.

## Contact

| Column         | Notes                                                                                                                                                         |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`           | UUID primary key (see above). Not fillable.                                                                                                                   |
| `first_name`   | Required, not null.                                                                                                                                           |
| `last_name`    | Required, not null.                                                                                                                                           |
| `email`        | Nullable. Stored lower-cased (see `contacts-validation`).                                                                                                     |
| `email_domain` | Nullable, derived from `email` on save (the part after `@`). Exists only so domain search can use an index. Never accepted as input or returned in responses. |
| `phone`        | Nullable. Stored in canonical E.164 only (`+61…` or `+64…`).                                                                                                  |
| timestamps     | `created_at`, `updated_at`.                                                                                                                                   |

- Enforce "email or phone" in the database as well: `CHECK (email IS NOT NULL OR phone IS NOT NULL)`, added with `DB::statement` in the migration.
- `$fillable` contains only `first_name`, `last_name`, `email` and `phone`.
- Model class: `App\Features\Contacts\Models\Contact`. `newFactory()` returns `Database\Factories\ContactFactory`.

## ContactCall

- Table `contact_calls`. Model `App\Features\Contacts\Models\ContactCall`, with a UUID primary key and the same `HasUuids` pattern as Contact.
- Belongs to Contact through `contact_id` (a UUID foreign key, indexed).
- Stores the dialled E.164 number at call time, so the history stays correct if the phone is edited later.
- `status` (lifecycle) and `outcome` (mocked result) are backed string enums in `App\Features\Contacts\Enums\`. Store them as strings, not PostgreSQL enum types.

## Indexes (based on actual queries)

Add an index only for a query the API or UI actually runs, and confirm it against the generated SQL (`EXPLAIN`):

- The primary keys already cover lookup by `id`.
- `phone` btree, for exact search on the normalised E.164 value.
- `email_domain` btree, for exact search on the stored lower-cased value.
- Names: the index type depends on the match mode. A contains-search with `ILIKE '%term%'` cannot use a btree. Use a btree on `lower(...)` for prefix search, or a `pg_trgm` GIN index for contains-search (needs the extension). Write either with `DB::statement`.
- Indexes for the allow-listed sort columns, with `id` as the tie-breaker.
- `contact_calls (contact_id, created_at)` for a contact's call history.

## Cache keys

- Use the `Cache` facade with the configured driver. No Redis infrastructure and no third-party packages unless a measured need justifies it.
- All keys live in `App\Features\Contacts\Support\ContactCache`. Cache raw attribute arrays, not models (`cache.serializable_classes` is `false`).
- Contact detail with latest calls: `contacts:detail:{id}`.
- Unfiltered listing pages only: `contacts:list:{version}:{hash}`. The hash covers every input that changes the result: filters, sort, direction, page and page size.
- `contacts:list:version` is a random token replaced after every contact write, which invalidates all cached pages at once. The default `database` store has no tags, so do not use tags.
- Always set an explicit TTL.

## Decisions

1. One email and one phone per contact.
2. Business upsert key: the normalised email. `email` has a unique constraint (PostgreSQL allows many NULLs). `phone` is not unique. The UUID `id` identifies a contact for explicit updates, but it is not the upsert key.
3. Name search: case-insensitive prefix match on first or last name. No `pg_trgm`.
4. Hard delete. `contact_calls.contact_id` uses `cascadeOnDelete`, so deleting a contact removes its calls.
5. Call outcomes: `answered`, `busy`, `no_answer`, `voicemail`, `failed`. Call status: `pending`, `completed`, `failed`. A `failed` status means processing failed, not the call outcome.

## Testing expectations

- Factory states: email-only, phone-only, AU phone, NZ phone.
- Tests that `id` is a generated UUID and used for route binding, `contact_calls.contact_id` references it, `email_domain` is derived, and the CHECK constraint rejects a row with neither email nor phone.
- Tests for the foreign key behaviour on delete.
