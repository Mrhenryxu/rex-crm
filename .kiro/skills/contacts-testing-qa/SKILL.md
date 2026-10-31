---
name: contacts-testing-qa
description: Test strategy and quality checks for the Contacts module, including PHPUnit layout, test database, transaction and queue tests, frontend and Playwright coverage, and pre-PR checks. Use when writing tests, choosing what to test, running the suite or preparing the PR.
---

# Contacts Testing and QA

## Purpose

The brief stresses testing business logic and its rules. This skill sets where tests go, what must be covered, how to run them and what the README contains. The other Contacts skills list their own test cases. This skill covers the parts they share.

## Layout

```text
tests/Unit/Contacts/      phone/email logic, enums, pure helpers (no DB)
tests/Feature/Contacts/   Actions (DB), HTTP endpoints, Artisan commands, jobs
```

- PHPUnit only. The project has no Pest. Do not add testing packages without approval.
- Use `RefreshDatabase` and factory states, not ad-hoc arrays.
- Name tests after the rule they prove (`test_contact_requires_email_or_phone`).

## Test database

- `phpunit.xml` currently uses SQLite in memory. Contacts relies on PostgreSQL behaviour (`ILIKE`, CHECK constraints, expression or trigram indexes), so run the suite against PostgreSQL. The connection switch is pending, together with the Docker Compose setup.
- Never weaken a test or drop a constraint to make the suite pass on SQLite.

## Required coverage (summary)

| Area         | Must prove                                                                                                                                                                                    |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Validation   | Required names, AU/NZ E.164 accept and reject cases, email validity and normalisation, email-or-phone, the same messages from HTTP and CLI, backend rejecting input the frontend would accept |
| Identifiers  | UUID `id` generated on create, used in routes and payloads, UUID foreign keys, malformed UUIDs rejected                                                                                       |
| Actions      | Upsert create/update, delete, search filters, sort allow-list, pagination limits, run without HTTP or CLI                                                                                     |
| Transactions | A failure partway through leaves no rows, no dispatched jobs and no cache changes                                                                                                             |
| Caching      | Keys differ per parameter set, invalidation after commit                                                                                                                                      |
| Queue        | Job dispatched after commit only, idempotent, `failed()` handling, each call outcome                                                                                                          |
| API          | Status codes, JSON shape, pagination metadata, informative 404/409/422/429, UTC timestamps                                                                                                    |
| CLI          | Exit codes, output, delete confirmation                                                                                                                                                       |
| Performance  | Query-count assertion on search and list endpoints (no N+1)                                                                                                                                   |

## Frontend

- The frontend test cases (Playwright workflows and form validation) are listed in `contacts-frontend`.
- Component and form-validation tests need a runner (for example Vitest and Testing Library). That is a new dependency and needs approval.
- Query elements by role and label, which also checks the accessible labels.

## README (short and operational)

Include only: a brief project overview, prerequisites and environment setup, running the app and the queue worker, CLI commands, test commands, and what the tests cover.

Do not include long architecture essays, extensive API docs, a roadmap, an AI diary, a catalogue of design decisions or restated requirements.

The brief also asks for notes on code structure and architecture, concessions and improvements, and approach, tooling and AI use. The user writes those sections. Do not generate or rewrite them.

When test commands or setup change, update the README in the same change.

## Running and reporting

- Backend: `php artisan test` (through Docker Compose once it exists). Frontend: the scripts defined in `package.json`.
- Report tests as passing only after running them, and quote the result.

## Pre-PR checklist

- `vendor/bin/pint --dirty --format agent` has been run on changed PHP files (see `AGENTS.md`).
- The backend suite passes against PostgreSQL, and the Playwright suite passes.
- No secrets in `.env.example`, fixtures or logs.
- The README follows the scope above.
