---
name: contacts-calls-queue
description: Mocked phone calls to contacts, call outcomes, ContactCall lifecycle and the queued job that processes calls. Use when implementing call initiation, the mock dialler, call outcomes, the call job, retries or sync vs async call execution.
---

# Contacts Calls and Queue

## Purpose

The brief requires that contacts can be called, with "a phone call placed — does not actually have to work but mocked response should consider possible outcomes". It also requires business logic usable both synchronously and asynchronously. This skill covers both.

## Flow

1. `PlaceContactCall` Action: inside a transaction, check the contact has a phone (else `ContactHasNoPhone`, HTTP 422, CLI `FAILURE`), create a pending `ContactCall` with a snapshot of the E.164 number, and register cache invalidation after commit. In async mode it dispatches `ProcessContactCallJob` with `->afterCommit()`.
2. `ProcessContactCall` Action: locks the call row, asks `CallSimulator` for an outcome, and stores `status`, `outcome` and `completed_at`. A missing or non-pending call is returned unchanged (idempotent).
3. Sync path (`mode=sync` over HTTP, `--sync` on the CLI): step 1, then step 2 in the same request. If step 2 throws, `MarkContactCallFailed` marks the call failed and the error is rethrown.
4. Async path: `ProcessContactCallJob` calls `ProcessContactCall`. The job holds no logic of its own.

HTTP, CLI and the job use these Actions. Nothing else changes call state.

## Call simulator

- `App\Features\Contacts\Support\CallSimulator::simulate(string $phone): CallOutcome` picks a random outcome. It is a concrete, non-final class so tests can swap it with `$this->mock(CallSimulator::class)`. Do not add an interface until a real telephony integration exists.

## Job

- `final class ProcessContactCallJob implements ShouldQueue` with `Queueable`. The constructor takes the call UUID (`string $callId`), not the model, so a deleted call is a no-op rather than a `ModelNotFoundException`.
- `$tries = 3`, `$backoff = [10, 30]`. `failed()` calls `MarkContactCallFailed`, which only changes a still-pending call.
- The queue connection is `database` (core Laravel). No Redis or Horizon.

## API and CLI contract

- Async HTTP response: `202 Accepted` with the pending `ContactCallResource`. Sync: `201` with the completed call.
- `GET /contacts/{contact}` (`latest_calls`) and `contacts:show` expose the latest 10 calls with status and outcome.
- Calls use the strictest rate limiter (`contacts-calls`), which returns 429 with `Retry-After`.

## Decisions

1. Outcomes: `answered`, `busy`, `no_answer`, `voicemail`, `failed`. Outcomes are results, not errors, so none of them is retried. Only unexpected exceptions are retried, up to 3 attempts.
2. HTTP accepts `mode` = `async` (default, 202) or `sync` (201). The SPA uses `sync`.
3. Call history is deleted with the contact (cascade).
4. The job receives the call UUID, not the model. A missing call is a successful no-op and is not retried.

## Testing expectations

- One test per outcome with a mocked `CallSimulator`.
- Dispatch after commit: use the real `database` queue driver and assert the `jobs` table, because `Queue::fake()` ignores `afterCommit`.
- Running the job directly stores the outcome. Running it a second time changes nothing (idempotency).
- `failed()` marks the call failed.
- Calling a contact without a phone is rejected with an informative error.
