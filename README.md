# Rex CRM

A CRM Contacts application built with Laravel 13, PHP 8.5, React, TypeScript and Mantine UI. The application uses a modular monolith architecture, with Contacts as the first business feature.

## Getting Started

### Prerequisites

- Docker and Docker Compose

PHP, Composer, Node.js and PostgreSQL are provided through the Docker environment.

### Start the application

```bash
docker compose up -d
```

The application entrypoint handles environment initialisation, dependency installation, application key generation, database migrations, local seed data and frontend asset compilation.

Once started, access the application at:

- Frontend: http://localhost:8000

For a fresh local database:

```bash
docker compose exec rex-crm php artisan migrate:fresh --seed
```

### Testing commands

```bash
# Backend tests
docker compose exec rex-crm php artisan test

# Frontend tests
docker compose exec rex-crm npm test

# TypeScript checks
docker compose exec rex-crm npm run typecheck

# Code style
docker compose exec rex-crm vendor/bin/pint --test

# End-to-end tests
docker compose run --rm playwright
```

Run `npm run build` before executing the Playwright suite if the environment requires a production asset build.

## Architecture and Design Decisions

### Modular monolith

I checked the Rex CRM website and have this idea how it should work. Thus, the application is structured as a modular monolith, with the Contacts feature located under `app/Features/Contacts`. Frontend is under `resources/js/features/contacts` and shared components.

This keeps related business logic together while retaining a single Laravel application, database and deployment unit. It provides clear feature boundaries without introducing the operational complexity of microservices or separate packages.

The structure also leaves room for additional business features as requirements evolve.

### Reusable business logic

Business operations are implemented as reusable Actions. HTTP controllers, Artisan commands and queued jobs act as entry points and delegate to the same business logic.

This avoids duplicating business rules across different interfaces and allows operations to be executed synchronously or asynchronously.

Controllers and commands are intentionally kept thin. I avoided introducing repository classes, generic service layers or additional abstractions where Laravel and Eloquent already provide the required functionality.

### Data integrity and transactions

Database transactions are used for operations involving multiple related records. Database constraints provide an additional layer of protection for data integrity.

Contact deletion also removes associated call records through the configured relationship and database behaviour.

UUIDs are used as externally exposed identifiers, avoiding the need to expose sequential database identifiers through the application interfaces. UUIDs are identifiers, not a substitute for authorisation.

### API and frontend separation

The backend is the source of truth for validation, business rules, filtering, sorting and pagination.

The React SPA communicates through the versioned `/api/v1` API. Search and listing operations are processed by the backend rather than relying on frontend-only filtering.

Mantine provides the frontend component foundation, with TypeScript helping maintain consistency across API data and UI components.

### Laravel-native capabilities

Where practical, the implementation uses Laravel's built-in features for validation, database transactions, queues, caching, rate limiting and Artisan commands.

This reduces external dependencies and keeps the application aligned with the Laravel ecosystem.

## Future Improvements

The implementation prioritises the assessment requirements and a maintainable foundation within the available development time.

Given more time or a broader production scope, I would consider the following improvements:

- **Authentication and authorisation:** Add user authentication, permissions and tenant isolation before exposing the application to multiple customers. These were intentionally excluded from the assessment scope.
- **Phone validation:** The current AU/NZ validation implements the required format and numbering rules. Integrating a maintained phone-number metadata library would provide more comprehensive numbering-plan validation and better support for future countries.
- **Caching:** The current database-backed cache provides limited performance benefits. I would benchmark actual usage and consider Redis or another dedicated cache store if the workload justified it.
- **Observability:** Introduce structured logging, monitoring and alerting for API failures, queue processing and application performance.
- **Automated testing:** Expand end-to-end coverage, accessibility checks and browser compatibility testing, particularly around more complex user workflows.
- **Performance:** Review query plans and production usage metrics to refine indexes, caching and pagination strategies as data volumes grow.

I have deliberately avoided implementing speculative features or introducing infrastructure that is not justified by the current requirements.

## Development Approach and AI Tooling

Kiro IDE was used as the primary AI-assisted development environment. Approx 600 tokens used.

The development process used project steering files and focused Skills to establish requirements, architecture, coding conventions and implementation guidance before generating code.

AI assistance was used for implementation support, test generation and code review. Generated code and recommendations were reviewed against the requirements, project conventions and test results rather than accepted without verification.

The implementation decisions and trade-offs are documented here to make the solution easier to review and extend.
