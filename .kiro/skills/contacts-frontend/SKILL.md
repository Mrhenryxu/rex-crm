---
name: contacts-frontend
description: Contacts React SPA, including the Mantine app shell, light/dark mode, navigation, contacts listing, search/filter form, contact forms, phone input, API client and frontend tests. Use when building or changing any React page, component, hook, schema or API call under resources/js.
---

# Contacts Frontend

## Purpose

A React SPA that uses most of the Contacts API. The brief asks for pragmatic, standard components. The backend owns business rules, validation and querying. The frontend manages form and control state and renders API results.

## Stack

- React, TypeScript in strict mode, Vite (the existing `vite.config.js`).
- Mantine as the primary UI component system. Use its theme and colour-scheme features for light/dark mode.
- React Hook Form for form state, with Zod schemas for validation and type inference.
- A maintained phone-number library for AU/NZ country selection, formatting and input help.
- Do not build a parallel component library or styling system. Use Mantine components and theme tokens before writing custom CSS.
- Tailwind (`tailwindcss-development` skill, current Vite setup) and the dark-only theme in `tech.md` stay as they are for now. Do not add new Tailwind styling that duplicates what a Mantine component or prop already does.
- All of these packages are new dependencies. Install them only after approval.

## App shell

- A top header with the logo and product name on the left, and a light/dark switch on the right. Persist the colour scheme with Mantine's colour-scheme manager.
- Left navigation: icon-only when collapsed, at about 5% of the viewport width (with a sensible minimum). When open, it expands to a practical fixed width. The main content uses the remaining width.
- When collapsed, every navigation item needs an `aria-label` and a tooltip. Items are reachable and usable with the keyboard, and focus is always visible.
- On small screens the navigation becomes a drawer opened from a burger button in the header.
- Use Mantine `AppShell` for this layout rather than a custom layout.

## Contacts listing

- A table with columns for name, email, phone and actions. The name cell shows an avatar circle with initials taken from `first_name` and `last_name`. There is no image upload.
- The search and filter form has fields for name, phone and email domain, with explicit Search and Clear buttons. Pressing Search sends the request. Clear resets the fields and reloads the default list. Typing does not trigger requests.
- Sorting and pagination controls send `sort`, `direction`, `page` and `per_page` to the API. Never filter, sort or paginate on the client. Render the API's `data` and `meta`.
- Keep query state in the URL search parameters so links and the back button work.
- Show loading, empty and error states with Mantine components.

## Contact forms and actions

- Create and edit forms for `first_name`, `last_name`, `email` and `phone`. The Zod schema mirrors the backend rules (required names, valid email, AU/NZ phone, at least one of email or phone) for quick feedback only.
- Send the phone value in E.164 format from the phone input.
- Map the API's 422 `errors` object onto the matching form fields with `setError`. Show the backend message as is. The backend has the final say, so always show its errors even if Zod passed.
- Delete uses a confirmation modal. Place-call shows the pending state, then the status and outcome from the call history.
- Use the UUID `id` from the API in routes (`/contacts/:id`).

## Code layout and API access

- Follow the frontend structure in `structure.md`: `resources/js/features/contacts/{api,components,hooks,schemas,types,pages}`, shared pieces in `components/ui` and `lib`.
- All HTTP calls go through one small API client in `lib`. It sets JSON headers, parses Laravel's error format into a shared error type, and handles 404, 422 and 429 (showing the retry time). Components never call `fetch` directly.
- API response types live in `features/contacts/types` and match the API Resources from `contacts-http-api`, including the pagination `meta`.

## Accessibility

- Use semantic elements: `header`, `nav`, `main`, table headers, and labelled form fields.
- Every icon-only button needs an accessible name.
- Check contrast in both light and dark schemes.

## Open decisions

1. Which phone-number library to use (for example `react-phone-number-input` or `libphonenumber-js`), and whether it is restricted to AU/NZ.
2. Client-side routing (React Router or similar) and server-state handling (for example TanStack Query or plain hooks). Both are new dependencies.
3. Whether contact create and edit use a page or a modal.

## Testing expectations

Playwright covers these workflows:

- Search with the Search and Clear buttons. Assert on the API request parameters, because results come from the backend.
- Sorting and pagination.
- Create and edit, including field errors returned by the backend.
- Delete with confirmation.
- Placing a call and seeing its outcome.
- The light/dark switch.
- Keyboard access to the collapsed navigation.

Form-validation tests for the Zod schemas run once a test runner is approved (see `contacts-testing-qa`).
