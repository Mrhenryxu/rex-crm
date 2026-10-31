---
name: contacts-cli
description: Contacts Artisan commands for upsert, delete, search, show and call. Use when adding or changing a contacts:* command, its arguments, options, output or exit codes.
---

# Contacts CLI

## Purpose

Exposes the same Contacts operations as the API through Artisan. The brief requires both an API and a CLI. Commands parse input, validate it with the shared rules, call an Action and print the result. They contain no business logic.

## Commands

Commands live in `App\Features\Contacts\Console\Commands`. Each is a `final` class extending `Illuminate\Console\Command`. The `{id}` argument is always the contact's UUID. A malformed UUID is a validation failure (`self::INVALID`).

| Signature (proposed)                                                                                      | Action                                    |
| --------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| `contacts:upsert {--id=} {--first-name=} {--last-name=} {--email=} {--phone=}`                            | UpsertContact                             |
| `contacts:show {id}`                                                                                      | read                                      |
| `contacts:search {--name=} {--phone=} {--email-domain=} {--sort=} {--direction=} {--page=} {--per-page=}` | SearchContacts                            |
| `contacts:delete {id} {--force}`                                                                          | DeleteContact                             |
| `contacts:call {id} {--sync}`                                                                             | call Actions (see `contacts-calls-queue`) |

- Option names map one-to-one to the API fields. Whether `--id` exists depends on the upsert identity decision.
- Register the commands in `bootstrap/app.php` with `->withCommands([__DIR__.'/../app/Features/Contacts/Console/Commands'])`.

## Behaviour

- Validate with `Validator::make()` using the shared rules. On failure, print every field error and return `self::INVALID`.
- Unknown UUID: print `Contact not found.` and return `self::FAILURE`. Domain exceptions: print the message and return `self::FAILURE`. Success: return `self::SUCCESS`.
- Show the UUID `id` in output. Show timestamps in the app timezone (Australia/Brisbane) and label the timezone.
- `contacts:delete` asks for confirmation unless `--force` is passed. With `--no-interaction` and no `--force`, it stops and returns `self::FAILURE`.
- `contacts:search` uses the same allow-lists and page size limits as the API and prints the pagination details.

## Constraints

- Commands never write through Eloquent directly. Reads go through `SearchContacts` or the read path, so filtering and caching match the API.
- No rate limiting on the CLI. Limits apply to API routes only.

## Testing expectations

- Feature tests with `$this->artisan(...)` using `expectsOutputToContain()`, `expectsConfirmation()` and `assertExitCode()`.
- For each command: success, a validation failure with the same messages as the API, and an unknown or malformed UUID. For delete: with and without `--force`.
- `contacts:call`: with `Queue::fake()` the job is pushed. With `--sync` the outcome is stored and printed.
