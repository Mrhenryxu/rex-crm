---
name: contacts-calls-queue
description: Mocked phone calls to contacts, call outcomes, ContactCall lifecycle and the queued job that processes calls. Use when implementing call initiation, the mock dialler, call outcomes, the call job, retries or sync vs async call execution.
---

# Contacts Calls and Queue

## Purpose

The brief requires that contacts can be called, with "a phone call placed — does not actually have to work but mocked response should consider possible outcomes". It also requires business logic usable both synchronously and asynchronously. This skill covers both.

## Flow

1. `InitiateContactCall` Action: inside a transaction, check the contact has a phone, create a `ContactCall` (UUID primary key, table `contact_calls`) with status `pending` and a snapshot of the E.164 number, and return it. In async mode, dispatch the job with `DB::afterCommit` (or `->afterCommit()` on dispatch).
2. `CompleteContactCall` Action: ask the mock dialler for an outcome, then store `outcome`, `status` and `completed_at` in a transaction.
3. Sync path (`--sync` on the CLI, or the HTTP option if approved): call 1 then 2 in the same request.
4. Async path: `PlaceContactCallJob` calls `CompleteContactCall`. The job holds no logic of its own.

HTTP, CLI and job all use these two Actions. Nothing else changes call state.

## Mock dialler

- One small concrete class in the feature (for example `MockDialler`) with `dial(string $e164): CallOutcome`. Do not add an interface or a provider abstraction until a real telephony integration exists.
- Outcomes must be deterministic when tested. Tests swap the instance through the container (`$this->instance(MockDialler::class, …)`) or seed the randomness.
- `CallOutcome` is a backed string enum. The list of outcomes is an open decision. The brief only says "possible outcomes". A plausible set to confirm is answered, no answer, busy, voicemail and failed. Do not ship the set until it is approved.
- A contact with no phone cannot be called. Throw a domain exception that maps to HTTP 422 and CLI `FAILURE`.

## Job

- `final class PlaceContactCallJob implements ShouldQueue`, using the `Queueable` trait. Pass the `ContactCall` model (serialised as an ID) to the constructor.
- Make it idempotent: if the call is no longer `pending`, return without changes. This makes retries and duplicate deliveries safe.
- Set `$tries` and `$backoff` explicitly. Implement `failed(Throwable $e)` to mark the call `failed` through an Action, so state never stays `pending` forever.
- If the contact is deleted before the job runs, follow the delete decision. Use `$deleteWhenMissingModels = true` if calls cascade on delete.
- The queue connection is `database` (core Laravel). No Redis/Horizon packages.

## API and CLI contract

- Async HTTP response: `202 Accepted` with the pending `ContactCallResource`. Sync: `201` with the completed call.
- The call-history endpoint and `contacts:show` expose status and outcome. See `contacts-http-api` and `contacts-cli`.
- Calls use the strictest rate limiter (`contacts-calls`). It returns 429 with `Retry-After` when exceeded.
- Responses, the CLI and the job identify calls and contacts by their UUID `id`. The job payload carries the model, which serialises to that UUID.

## Open decisions

1. Approved outcome list and whether some outcomes count as retryable.
2. Default execution mode for HTTP (async with 202 is the recommendation).
3. Whether the call history is kept after a contact is deleted.

## Testing expectations

- Unit/feature tests for both Actions with a deterministic dialler, one test per outcome.
- `Queue::fake()`: initiation pushes `PlaceContactCallJob` with the right call, and nothing is pushed when the transaction rolls back.
- Running the job directly stores the outcome. Running it a second time changes nothing (idempotency).
- `failed()` marks the call failed.
- Calling a contact without a phone is rejected with an informative error.
