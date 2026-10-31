---
name: contacts-validation
description: Contact input validation, AU/NZ phone validation and E.164 normalisation, email validation and the email-or-phone rule. Use when writing or changing validation rules, Form Request rules, CLI input validation or phone formatting.
---

# Contacts Validation

## Purpose

One definition of valid contact input, shared by HTTP, CLI and queued entry points. The backend is authoritative.

## Business rules

From the brief (authoritative):

- Phone numbers and emails must be valid.
- Phone numbers must be in E.164 format and only Australian (`+61`) or New Zealand (`+64`).

From the approved plan:

- `first_name` and `last_name` are required.
- `email` and `phone` are optional, but at least one must be supplied.
- Email is normalised the same way everywhere. Phone is stored in canonical E.164.

## Where rules live

- `App\Features\Contacts\Rules\AuNzPhoneNumber`: implements `Illuminate\Contracts\Validation\ValidationRule`.
- One small final class for parsing and formatting phone numbers, with pure static methods. The rule, the upsert Action and search all use it, so the logic exists once.
- Field rule arrays are defined once in the feature. The Form Request returns them from `rules()`. The CLI command uses `Validator::make($input, <same rules>)`. Never copy rule arrays between entry points.
- Actions accept only validated input and do not validate again. Database constraints are the final guard.

## Frontend input assistance

- The frontend may use a maintained phone-number library for country selection (AU/NZ only), formatting and input help. The frontend Zod schema mirrors these rules for user feedback.
- Never trust frontend validation. The backend must validate the submitted value itself against the AU/NZ E.164 rules, whatever the frontend library accepted.
- Backend phone validation must not use any third-party library (no libphonenumber in PHP).

## Phone rule

- Accepted and stored values are canonical E.164: `+`, country code, national number, no spaces, at most 15 digits.
- Starting patterns, to be checked against the ACMA and NZ numbering plans and covered by tests:
    - AU: `^\+61[23478]\d{8}$` (geographic 2/3/7/8, mobile 4).
    - NZ: `^\+64(?:[34679]\d{7}|2\d{7,9})$` (landline 3/4/6/7/9, mobile 2x).
- Reject a national trunk `0` after the country code (`+610412…`).
- Error messages say what is wrong and give the expected format, for example "The phone must be an Australian (+61) or New Zealand (+64) number in E.164 format, e.g. +61412345678." Never include regexes or internal details.

## Email rule

- Trim, then `email:rfc` and `max:254`. Do not use `dns`, because it makes network calls and makes tests non-deterministic.
- Normalise by trimming and lower-casing the whole address before saving it to the database and before comparing it (decided). Store `email_domain` as described in `contacts-domain`.

## Name rules

- `first_name` and `last_name`: `required|string|max:<n>`, trimmed. Whitespace-only names are rejected.

## Email-or-phone rule

- `email`: `nullable|required_without:phone`. `phone`: `nullable|required_without:email`. Use a custom message that names both fields.
- On update, check the rule against the resulting record, not just the submitted fields, so a partial update cannot remove the last contact method.

## Query parameters

- Validate every search, filter, sort and pagination parameter (see `contacts-http-api` for the names).
- Normalise a phone search term with the same phone class. Reject a malformed phone term or email domain with 422 rather than returning an empty list.
- `sort` and `direction` use `Rule::in()` with the allow-lists. `per_page` uses `integer|min:1|max:<max>`.

## Open decisions

1. Strict E.164 input only (the brief's wording), or also accept local formats such as `0412 345 678` and convert them. Accepting local formats loosens the brief and needs explicit approval. Until then the default is strict, and the frontend library sends E.164.
2. Whether AU special-service ranges (13/1300/1800, satellite) are in scope. The default is no.
3. Maximum lengths for the name fields.

## Testing expectations

- Unit tests for the phone class and rule: valid AU and NZ mobile and landline numbers, other country codes, trunk zero, too short, too long, spaces, missing `+`.
- Unit tests for email: valid, invalid, trimming and lower-casing.
- Feature tests: required names, missing both email and phone on create and on update, and HTTP and CLI returning the same field messages for the same invalid input.
- A test that the API rejects an invalid phone sent directly, bypassing the frontend.
