# Rex CRM

A Laravel 13 (PHP 8.5) modular monolith with a React + Mantine SPA. The first module, Contacts (`app/Features/Contacts`), stores contacts and places mock phone calls through a JSON API (`/api/v1`) and Artisan commands.

## Setup

Prerequisites: Docker with Docker Compose. PHP, Composer and Node.js run inside the containers.

| Container       | Purpose                                                    | Host port  |
| --------------- | ---------------------------------------------------------- | ---------- |
| `rex-crm`       | Laravel app (`php artisan serve`), Composer, Node.js, Vite | 8000, 5173 |
| `rex-crm-queue` | Queue worker (`queue:listen`, `database` driver)           | none       |
| `rex-crm-pgsql` | PostgreSQL 18 (`rex_crm`, and `rex_crm_testing` for tests) | 5432       |

```sh
cp .env.example .env            # local development defaults only; change the DB password outside local use
docker compose build
docker compose up -d
docker compose exec rex-crm composer install
docker compose exec rex-crm npm install
docker compose exec rex-crm php artisan key:generate
docker compose exec rex-crm php artisan migrate
docker compose exec rex-crm npm run build
```

Open http://localhost:8000. Host ports can be changed with `APP_PORT`, `VITE_PORT` and `FORWARD_DB_PORT`.

- Hot reload instead of a build: `docker compose exec rex-crm npm run dev`
- Queue worker: runs in `rex-crm-queue` and processes async calls. Logs: `docker compose logs -f rex-crm-queue`
- Stop: `docker compose down` (add `-v` to delete the database volume)

Optional `.env` settings, with defaults, are in `config/contacts.php`: page size (15, maximum 100), per-IP rate limits per minute (read 120, write 30, calls 10) and cache TTLs. The cache uses the configured `database` store. It caches contact details and unfiltered listing pages only. On the database store, a cache hit is still a database query, so the gain is small.

## Testing

```sh
docker compose exec rex-crm php artisan test               # backend (PHPUnit, against rex_crm_testing)
docker compose exec rex-crm npm test                       # frontend (Vitest + Testing Library)
docker compose exec rex-crm npm run typecheck              # TypeScript strict mode
docker compose run --rm playwright                         # end-to-end smoke test (needs npm run build first)
docker compose exec rex-crm vendor/bin/pint --test         # PHP code style
```

The end-to-end test runs in the official Playwright image against the running app and development database, and deletes the records it creates.

## CLI commands

```sh
docker compose exec rex-crm php artisan contacts:upsert --first-name=Jane --last-name=Citizen --email=jane@example.com --phone=+61412345678
docker compose exec rex-crm php artisan contacts:upsert --id=<uuid> --first-name=Jane --last-name=Citizen --phone=+6421123456
docker compose exec rex-crm php artisan contacts:show <uuid>
docker compose exec rex-crm php artisan contacts:search --name="jo smi" --email-domain=example.com --sort=created_at --direction=desc --page=1 --per-page=25
docker compose exec rex-crm php artisan contacts:call <uuid> [--sync]
docker compose exec rex-crm php artisan contacts:delete <uuid> [--force]
```

- `contacts:upsert` without `--id` updates the contact with the same email, otherwise creates one. Email or phone is required, and phones must be AU/NZ E.164.
- `contacts:call` queues the call by default. `--sync` places it now and prints the outcome.
- `contacts:delete` asks for confirmation unless `--force` is given.
- Exit codes: `0` success, `1` failure (for example, contact not found), `2` invalid input.

## Test coverage

These areas are covered. No coverage percentage has been measured.

- **Validation:** required names, email-or-phone, AU/NZ E.164 phones, email normalisation, search parameter validation, and the same messages from the API and the CLI.
- **Actions:** upsert by email, update by UUID, duplicate-email conflict, delete with calls, latest 10 calls, filters, sort allow-list, pagination limits, stable ordering, and query counts.
- **Database:** UUID keys, CHECK and unique constraints, cascade delete, and transaction rollback.
- **Calls and queue:** every outcome, dispatch only after commit, idempotent processing, a missing call as a no-op, 3 attempts, and the `failed()` handling.
- **API:** every endpoint, status codes, JSON shape, UTC timestamps, and 404, 409, 422, 429 and 500 responses without internal details.
- **Rate limiting:** separate read, write and call limits, per-IP tracking, and reset after the window.
- **Caching:** cache hits, invalidation after commit, and no invalidation on rollback.
- **CLI:** every command, its output, exit codes and delete confirmation.
- **Frontend:** the shell, collapsible navigation, theme switch, initials avatars, Search and Clear, backend query parameters, forms and phone input, backend errors, loading and empty states, and the call and delete flows.
- **End-to-end:** create, search, paginate, sort, call and delete.
