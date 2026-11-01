# Architecture and Project Structure

## Architecture

Use a **modular monolith architecture**.

- The application is a single Laravel application, deployed as one unit, using one PostgreSQL database.
- Do not introduce microservices, separate applications, standalone Composer packages or separate databases.
- Organise backend code by business feature under `App\Features\`.
- Each feature owns its business logic, models and domain-specific rules.
- Keep business logic independent of HTTP, CLI and queue entry points.
- Use shared application Actions to implement reusable business operations.
- Introduce new features as separate namespaces without unnecessarily changing existing features.
- Prefer clear boundaries and minimal coupling between features.

## Backend Structure

- Keep HTTP controllers, Artisan commands and queue jobs thin.
- Use reusable Actions for business operations.
- Controllers, commands and jobs must delegate to the same Actions rather than duplicate business logic.
- Use Form Requests for HTTP input validation.
- Use reusable validation rules for business-specific validation.
- Use API Resources for consistent API responses.
- Use Eloquent models for persistence and relationships.
- Use database transactions for operations requiring atomicity.
- Queue jobs should handle asynchronous execution and delegate business operations to Actions.
- Keep business rules within the feature rather than embedding them in delivery mechanisms.
- Follow Laravel conventions and use existing framework capabilities.
- Avoid unnecessary architectural layers, repositories, generic service classes and custom discovery mechanisms.
- Do not create abstractions or folders without a concrete requirement.

### Feature Layout

Group feature-related code under its own namespace.

The Contacts feature should use the following structure:

```text
app/Features/Contacts/
├── Actions/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Console/
│   └── Commands/
└── Jobs/
```

Folder responsibilities:

- `Actions/`: Reusable business operations.
- `Models/`: Eloquent models and relationships.
- `Http/Controllers/`: Thin HTTP entry points.
- `Http/Requests/`: HTTP input validation.
- `Http/Resources/`: API response representations.
- `Console/Commands/`: Thin Artisan entry points.
- `Jobs/`: Asynchronous execution entry points.

Additional folders such as `Enums/`, `Rules/` and `Exceptions/` may be introduced when required.

Do not create empty folders or placeholder classes.

Keep shared application-level functionality in standard Laravel locations under `app/`.

Follow Laravel conventions for:

- Database migrations, factories and seeders under `database/`.
- Route definitions under `routes/`.
- Application service providers under `app/Providers/`.
- Application bootstrap and exception handling under `bootstrap/`.

Register feature routes and commands using Laravel's native mechanisms. Avoid custom auto-discovery systems.

Organise tests by feature where practical:

```text
tests/
├── Feature/
│   └── Contacts/
└── Unit/
    └── Contacts/
```

## Frontend Structure

Organise React code by feature and responsibility.

- Keep Contacts-specific components, hooks, API integration, types and schemas within the Contacts feature.
- Keep shared application-wide components in a shared UI directory.
- Separate API communication from presentation components.
- Avoid duplicating API handling logic.
- Use shared types and consistent API error handling.
- Keep components focused and avoid unnecessary abstractions.

Example:

```text
resources/
├── js/
│   ├── app/
│   ├── components/
│   │   └── ui/
│   ├── features/
│   │   └── contacts/
│   │       ├── api/
│   │       ├── components/
│   │       ├── hooks/
│   │       ├── schemas/
│   │       ├── types/
│   │       └── pages/
│   └── lib/
└── css/
```

Introduce additional directories only when they improve code organisation.

## Design System

Maintain a small shared collection of reusable UI components.

Start with components required by the Contacts interface, such as:

- Button
- Input
- Badge
- Alert
- ConfirmationDialog

Introduce additional components only when needed.

Use native HTML elements and accessible component behaviour wherever practical.

Storybook may be used to document reusable components, but creating stories for every component is not mandatory.

## Code Quality

- Use clear and consistent naming.
- Prefer small, focused functions, classes and components.
- Avoid duplicated business logic.
- Keep responsibilities clearly separated.
- Follow established Laravel, React and TypeScript conventions.
- Add tests for important business rules and application behaviour.
- Keep API errors informative and suitable for third-party API consumers.
- Document significant architectural decisions when necessary.
- Prefer straightforward, maintainable solutions over unnecessary complexity.

## Engineering Constraints

- Preserve existing functionality and approved requirements.
- Do not introduce new architectural patterns or dependencies without justification.
- Do not weaken business rules, transaction guarantees or tests to simplify implementation.
- Prefer completing working end-to-end functionality before introducing optional abstractions or tooling.
