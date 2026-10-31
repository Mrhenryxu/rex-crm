# Rex CRM

A Laravel 13 (PHP 8.5) modular monolith with a React frontend. The first module is Contacts.

## Prerequisites

- Docker with Docker Compose. PHP, Composer and Node.js run inside the containers, so you don't need them on the host.

The stack (`docker-compose.yaml`, Compose project `rex-crm`):

| Container       | Purpose                                                       | Host port  |
| --------------- | ------------------------------------------------------------- | ---------- |
| `rex-crm`       | Laravel app (`php artisan serve`), Composer, Node.js 26, Vite | 8000, 5173 |
| `rex-crm-queue` | Queue worker (`queue:listen`, `database` driver)              | none       |
| `rex-crm-pgsql` | PostgreSQL 18, with databases `rex_crm` and `rex_crm_testing` | 5432       |

You can change the host ports with `APP_PORT`, `VITE_PORT` and `FORWARD_DB_PORT`.

## Setup

```sh
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec rex-crm composer install
docker compose exec rex-crm npm install
docker compose exec rex-crm php artisan key:generate
docker compose exec rex-crm php artisan migrate
```

The database credentials in `.env.example` are local development defaults only.

## Running

- App: http://localhost:8000. Health check: http://localhost:8000/up.
- Frontend dev server with hot reload: `docker compose exec rex-crm npm run dev`
- Production asset build: `docker compose exec rex-crm npm run build`
- Queue worker logs: `docker compose logs -f rex-crm-queue`
- Stop the stack: `docker compose down`. Add `-v` to also delete the database volume.

## Testing

The PHPUnit suite runs against the PostgreSQL database `rex_crm_testing`, as set in `phpunit.xml`.

```sh
docker compose exec rex-crm php artisan test
docker compose exec rex-crm vendor/bin/pint --test
```
