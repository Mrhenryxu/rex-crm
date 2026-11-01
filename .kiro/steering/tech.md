# Technology Standards

## Backend

- Laravel 13.
- PHP 8.5 and above.
- Use Laravel's built-in capabilities wherever possible.
- Backend functionality must use packages and dependencies provided by the core Laravel framework unless explicitly approved.
- Do not introduce third-party backend packages without approval.
- Use Eloquent ORM, migrations, validation, database transactions, queues and Artisan commands.
- Implement AU/NZ phone number validation and E.164 formatting without third-party dependencies.
- The backend is the source of truth for business rules and validation.
- Use reusable application Actions for business operations.
- HTTP controllers, CLI commands and queue jobs must delegate to shared Actions rather than duplicate business logic.
- Follow Laravel conventions and use strong typing.
- Avoid unnecessary services, repositories, interfaces and architectural abstractions.

## Frontend

- React.
- TypeScript with strict mode enabled.
- Tailwind CSS as the primary styling solution. Use SCSS only where necessary.
- React Hook Form for form state management.
- Zod for form validation and TypeScript type inference.
- Build reusable, accessible UI components using native HTML elements where appropriate.
- Use a consistent dark visual theme.
- Support responsive layouts.
- Follow accessibility fundamentals, including semantic HTML, keyboard navigation and visible focus states.
- Storybook may be used when it provides value for documenting reusable components; it is not required for every component.
- Treat visual references as design inspiration rather than requiring an exact reproduction of an existing product interface.

## Database

- PostgreSQL.
- Use Laravel's native PostgreSQL integration and Eloquent ORM.
- Manage schema changes through Laravel migrations.
- Use appropriate database constraints, foreign keys and indexes.
- Use database transactions for operations involving multiple related writes.
- Maintain data integrity and consistency.

## Development Environment

- Ubuntu 26.04 LTS.
- Docker Compose.
- Composer 2.x.
- Node.js 26 LTS.
- Use the existing Docker Compose environment for PHP, Composer and Node.js tooling.
- Avoid requiring local PHP, Composer or Node.js installations on the host.
- Prefer reusing existing Docker configuration over unnecessary infrastructure changes.
- Ensure essential development and testing commands can be executed through Docker Compose.

## Testing

- PHPUnit and Laravel testing tools for backend tests.
- Test important business rules independently of HTTP controllers and CLI commands.
- Cover critical validation, API responses, error handling and database behaviour.
- Test transaction rollback and asynchronous queue behaviour.
- Test important frontend interactions and form validation.
- Use Playwright for essential end-to-end workflows.
- Prioritise tests that verify assessment requirements over optional testing infrastructure.
- Do not introduce additional testing dependencies without approval.
- Never claim tests pass unless they have actually been executed successfully.
- Update README.md when testing commands or procedures change.

## Security

- Follow Laravel security best practices and use built-in protections where applicable.
- Prevent common vulnerabilities, including XSS, SQL injection and insecure mass assignment.
- Validate untrusted input and safely handle output.
- Use Eloquent and parameterised queries rather than unsafe SQL construction.
- Protect sensitive configuration and credentials. Never commit secrets.
- Avoid exposing sensitive information through API responses, logs or error messages.
- Authentication and authorisation are outside the current technical exercise scope unless explicitly required.
- Apply Laravel-native rate limiting to API routes, with separate limits where appropriate for read, write and mock-call operations.

## Engineering Principles

- Prioritise simple, maintainable and reliable solutions.
- Follow established framework conventions.
- Keep business logic separate from application entry points.
- Avoid unnecessary dependencies, abstractions and premature optimisation.
- Avoid N+1 queries and unnecessary database operations.
- Preserve existing functionality and tests when making changes.
- Never weaken or bypass tests simply to make an implementation pass.
- Document significant technical decisions and non-obvious implementation choices.
- Prefer working end-to-end functionality over unnecessary infrastructure or tooling.

## Application Configuration

- Application timezone: Australia/Brisbane.
- API timestamps must follow the approved API requirements, including UTC serialisation where specified.
