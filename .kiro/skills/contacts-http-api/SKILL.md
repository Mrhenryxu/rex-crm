---
name: contacts-http-api
description: Contacts JSON API, including routes, controllers, Form Requests, API Resources, status codes, error format, rate limiting and query parameters for filtering, sorting and pagination. Use when adding or changing any /api contacts endpoint or its response contract.
---

# Contacts HTTP API

## Purpose

Exposes the Contacts Actions over HTTP to the SPA and to third-party consumers. Each controller method validates with a Form Request, calls one Action and returns a Resource.

## Route registration

- The app has no `routes/api.php`. Do not run `php artisan install:api`, because it installs Sanctum and authentication is out of scope. Create `routes/api.php` and register it with `withRouting(api: …)` in `bootstrap/app.php`.
- Use the prefix `/api/v1/contacts` and default route-model binding `{contact}`, which resolves by the UUID primary key. Use `whereUuid('contact')` so a malformed ID returns 404 without reaching the database query.

## Endpoints

| Method      | Path                                           | Action          | Success                                           |
| ----------- | ---------------------------------------------- | --------------- | ------------------------------------------------- |
| GET         | `/contacts`                                    | SearchContacts  | 200, paginated                                    |
| GET         | `/contacts/{contact}`                          | read            | 200                                               |
| POST / PUT  | `/contacts` / `/contacts/{contact}`            | UpsertContact   | 201 created or 200 updated / 200                  |
| DELETE      | `/contacts/{contact}`                          | DeleteContact   | 204                                               |
| POST        | `/contacts/{contact}/calls`                    | call initiation | 202 async / 201 sync (see `contacts-calls-queue`) |

## Requests

- One Form Request per write and search endpoint, in `Http/Requests/`, using the shared rules from `contacts-validation`.
- Pass only `$request->validated()` (or typed data built from it) to the Action.
- Search parameters: `name`, `phone`, `email_domain`, `sort`, `direction`, `page` (or `cursor`) and `per_page`. The frontend uses these exact names.

## Responses

- `ContactResource` and `ContactCallResource` live in `Http/Resources/`. The identifier is `id` (UUID string). Never serialise `email_domain`.
- Return `first_name` and `last_name` separately. The frontend builds display names and initials from them.
- Timestamps are ISO 8601 in UTC (`toIso8601ZuluString()`), even though the app timezone is Australia/Brisbane.
- Lists use Resource collections, so `data`, `links` and `meta` (current page, per page, total or next cursor) have the same shape on every list endpoint.

## Errors (informative for third parties)

- 422: Laravel's `{ message, errors: { field: [...] } }` with field-specific messages, including for invalid query parameters.
- 404: replace the default `ModelNotFoundException` message, which leaks the model class name, with `Contact not found.` in `withExceptions()`. Other API 404s return `The requested resource was not found.`
- 409: upsert unique conflict, from the domain exception.
- 429: a short JSON message such as `Too many requests. Try again in N seconds.` plus the `Retry-After` and `X-RateLimit-*` headers. Do not reveal how the limiter is keyed.
- 500 responses never include stack traces or SQL outside debug mode.
- JSON rendering for `api/*` is already enabled in `bootstrap/app.php` (`shouldRenderJsonWhen`).

## Rate limiting

- Define named limiters with `RateLimiter::for()` in `AppServiceProvider::boot()`: `contacts-read`, `contacts-write` and `contacts-calls`. Key them by IP, since there is no authentication.
- Apply them with `throttle:<name>` per route group. Mock calls get the strictest limit.
- Read the limits from config, not inline literals.
- Rate limiting and UUIDs are not authorisation. Authentication and authorisation remain out of scope.

## Decisions

1. `POST /contacts` upserts by normalised email (201 created, 200 updated). `PUT /contacts/{contact}` replaces the given contact and returns 409 if the email belongs to another contact.
2. Per IP, per minute: read 120, write 30, calls 10. Page size: default 15, maximum 100. All values come from `config/contacts.php`.
3. Offset pagination (`page`, `per_page`). Sortable fields: `first_name`, `last_name`, `created_at`. Default sort: `last_name` ascending.
4. The call endpoint defaults to async. There is no separate call-history endpoint, because `GET /contacts/{contact}` returns the latest 10 calls.

## Testing expectations

- Per endpoint: success status and JSON structure, 422 field errors, 404 for an unknown or malformed UUID, and `id` returned as a UUID string.
- Filter, sort and pagination parameters: a sort field not on the allow-list returns 422, `per_page` over the maximum returns 422, and pagination metadata is present.
- One test per limiter: 429 with a JSON body and a `Retry-After` header.
- UTC timestamp format in responses.
