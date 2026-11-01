# Product Context

## Product

Rex CRM

## Overview

Rex CRM is a cloud-based Software as a Service (SaaS) Customer Relationship Management platform designed to help businesses manage customer information, relationships and related business interactions.

The platform provides a centralised environment for managing business data and workflows through a collection of integrated business modules.

Rex CRM follows a modular monolith architecture, with clearly defined business modules operating within a shared application platform.

## Product Vision

Rex CRM aims to become a scalable, secure, reliable and extensible SaaS CRM platform that supports businesses in managing their customer relationships and business operations.

The platform will evolve over time to support multiple business domains and capabilities while maintaining a consistent user experience and shared application infrastructure.

The architecture should accommodate new business modules and SaaS capabilities without requiring fundamental restructuring of the existing platform.

## Business Modules

### Contacts Module

The Contacts module manages customer and contact information and is the first business module within Rex CRM.

Its responsibilities include:

- Creating and updating contacts through an upsert operation.
- Deleting contacts.
- Retrieving individual contact records.
- Searching contacts by name, phone number and email domain.
- Validating email addresses.
- Validating phone numbers using E.164 format.
- Supporting Australian and New Zealand phone numbers.
- Managing mock phone call records and their outcomes.
- Exposing contact functionality through HTTP APIs and CLI.
- Supporting asynchronous operations.
- Providing a React-based user interface for contact management.

### Future Business Modules

Rex CRM is designed to accommodate additional business modules as the platform evolves.

Each module should have clearly defined responsibilities, own its domain-specific business rules and minimise unnecessary dependencies on other modules.

Additional modules will be introduced as product requirements emerge.

## SaaS Platform Principles

- **Multi-tenancy:** The platform should be capable of supporting multiple businesses with appropriate data isolation.
- **Security:** Customer and business information must be protected through appropriate security controls.
- **Scalability:** The platform should accommodate growing numbers of customers, users and business records.
- **Availability:** The platform should be designed for reliable and continuous service delivery.
- **Extensibility:** New business modules and platform capabilities should be introduced without unnecessary architectural changes.
- **Maintainability:** Platform functionality should remain understandable and straightforward to evolve.
- **Consistency:** Shared platform capabilities should provide consistent behaviour across business modules.

## Business Principles

- Each business module owns its domain-specific responsibilities and business rules.
- Business logic should be reusable across HTTP, CLI and asynchronous execution.
- Business rules should not be duplicated across application entry points.
- Database transactions should protect operations involving multiple resources.
- APIs should provide consistent responses and informative errors.
- Data integrity and reliability should be maintained across all operations.
- Modules should have clear boundaries and minimise unnecessary coupling.
- Prefer straightforward, maintainable solutions over unnecessary abstractions.
- Design for future extensibility without prematurely implementing functionality that is not currently required.

## Product Scope

The Contacts module is the current implementation scope.

Its functionality is defined by the approved Contacts requirements.

The broader SaaS platform vision does not require additional business modules or SaaS capabilities to be implemented until their requirements are defined.

## Non-Functional Priorities

- **Maintainability:** Keep the codebase understandable and straightforward to evolve.
- **Scalability:** Accommodate increasing data volumes, customers and additional business modules.
- **Performance:** Ensure efficient database access and application operations.
- **Reliability:** Preserve data consistency and handle failures predictably.
- **Security:** Protect customer information and prevent common application vulnerabilities.
- **Testability:** Make business rules and application behaviour independently testable.
- **Extensibility:** Allow new capabilities to be introduced without unnecessary changes to existing modules.
- **Usability:** Provide an intuitive and consistent user experience across the platform.
