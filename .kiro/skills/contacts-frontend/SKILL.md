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
- Do not build a parallel component library or styling system.
- Styling order: Mantine component props, then the Mantine theme (`createTheme`: colours, spacing, radius, fonts, component defaults), then Mantine style props or CSS modules for what the theme cannot express.
- Import `@mantine/core/styles.css` once in the SPA entry point. Wrap the app in `MantineProvider` with the shared theme.
- The theme (`resources/js/app/theme.ts`) uses tokens taken from rexsoftware.com: mint `#3DE0BF` primary, text `#1C1A33`, navy `#0F2B66` headings, wheat `#F7F5F1` page surface, `#E6E2D8` borders, and Inter with Sofia Sans Extra Condensed for headings (self-hosted with `@fontsource`, SIL OFL). Use theme colours and CSS variables, never hard-coded HEX values in components.
- Mint fails contrast as text on white (1.7:1). Don't use `c="rex"` or mint links in light mode. Filled mint buttons get dark text through `autoContrast`.
- Rex-specific styles the theme object can't express (button hover, focus ring, heading colour, page surface) live in `resources/js/app/theme.module.css`.
- Don't use the Rex logo without permission; the header keeps the generic icon with "Rex CRM" text.
- The existing Tailwind packages and `resources/css/app.css` stay installed but are not used by the SPA. Do not add Tailwind classes or utilities to React components. Make sure Tailwind's base reset does not load on SPA pages, so it cannot override Mantine styles.

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

## Decisions

1. Phone input uses `libphonenumber-js`, with an AU/NZ country `Select` and as-you-type formatting.
2. Routing uses `react-router`. Server state uses small hooks with `fetch`, not a server-state library.
3. Create and edit are pages. Icons come from `@tabler/icons-react`.
4. Component tests use Vitest and Testing Library. End-to-end tests use Playwright.

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
