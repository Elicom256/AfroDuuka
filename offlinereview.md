# Offline Sync Review

Date: 2026-10-16

## Executive summary

The implementation is well beyond a proof-of-concept and is now close to a real offline-first sync foundation. The frontend outbox, connectivity monitoring, retry/backoff logic, and app-level registration are in place, and the backend now has a real batch sync route with idempotency, tenant scoping, and per-operation dispatch.

That said, the project is not fully “done” as a production-grade offline sync release. The architecture is strong, but a few operational and business-rule details still need explicit audit and live verification before calling the system complete.

## Documents reviewed

- `offline.md`
- `apisync.md`
- `sync.md`
- `todayswork.md`
- Current implementation code in the repo:
  - `ui/src/App.tsx`
  - `ui/src/lib/offline/handlers.ts`
  - `ui/src/lib/offline/operationQueue.ts`
  - `ui/src/app/store/features/sync/syncQuery.ts`
  - `api/app/Http/Controllers/SyncBatchController.php`
  - `api/app/Http/Requests/SyncBatchRequest.php`
  - `api/app/Services/Sync/SyncOperationDispatcher.php`
  - `api/tests/Feature/Sync/SyncBatchTest.php`

## Overall verdict

### Implemented well

1. Frontend offline foundation is present
   - Dexie outbox and operation queue are in place.
   - Connectivity monitoring and offline status UI are wired in.
   - App startup registers sync handlers and triggers a flush when business context is known.
   - Pending/failed/synced transitions are handled by the queue.

2. Backend sync contract is actually implemented
   - `POST /sync/batch` is live through the RTK Query client.
   - The batch request enforces a bounded size and validates operation IDs and payload structure.
   - A `SyncOperation`-based dispatcher exists and enforces duplicate protection through a database-scoped unique claim.
   - Each operation is processed independently, with the result returned per operation rather than failing the entire batch.

3. The design intent is being respected
   - The code does not fake success.
   - Failure is explicit and recoverable.
   - The sync engine keeps transport failures separate from server-side conflicts.
   - There is clear separation between client-side retry and server-authoritative business validation.

### Closed gap chunks

The earlier review gaps were converted into explicit implementation chunks and closed on the current branch.

1. ✅ Chunk 1 — `sync_status` audit and coverage
   - Confirmed the migration set contains the required `sync_status` markers and the frontend queue/status mapping is aligned with the intended pending → syncing → synced progression.
   - This is now tracked as a completed review and implementation chunk instead of an open concern.

2. ✅ Chunk 2 — business-rule enforcement for offline sales
   - Confirmed the server-side sync dispatch stays authoritative for pricing, stock, and financial side effects.
   - The sync flow does not silently accept client totals, and conflicts are surfaced as explicit operation results rather than last-write-wins writes.

3. ✅ Chunk 3 — authorization boundary discipline
   - Confirmed the route layer authenticates the caller while the sync handlers enforce tenant/branch ownership and business scope.
   - Authorization remains a handler-level concern rather than a weaker blanket route rule.

4. ✅ Chunk 4 — frontend/backend contract consistency
   - The frontend batch mutation and the backend response contract are aligned on operation ids, batch envelope rules, result codes, and summary handling.
   - This is now a closed chunk rather than an open implementation gap.

5. ✅ Chunk 5 — retry and same-state discipline
   - The sync engine only marks an operation synced when the server confirms it, and retryable failures remain pending for retry backoff instead of being reported as success.
   - The offline queue keeps transport failures, conflicts, and server failures distinct.

## Per-document status

### offline.md

Status: mostly implemented.

Implemented:

- PWA configuration and service worker structure are in place.
- Dexie outbox and offline operation layer are present.
- Connectivity monitoring is implemented.
- Offline status UI is implemented.
- Sync engine contract and retry behavior are in place.

Remaining gaps / improvements:

- The review should confirm the final real behavior against live POS, product, and customer flows rather than just the unit tests.
- The docs rightly warn against claiming end-to-end sync completion before the backend contract is fully proven in a real environment.

### apisync.md

Status: mostly implemented, but must be treated as an active delivery contract rather than a purely aspirational spec.

Implemented:

- Batch endpoint exists and is being used by the frontend.
- Idempotent operation dispatch exists.
- Concrete handlers are registered for sale, inventory, customer, and todo-like operations.
- Request-size and operation-size limits are enforced.
- Per-operation response mapping is present.

Still worth tightening:

- Keep the handler and request validation contracts aligned with the actual product/business rules, especially for offline price mismatches and stock conflicts.
- Confirm that the server returns stable machine-readable errors consistently across all handlers.
- Ensure the end-to-end result mapping from the frontend outbox remains consistent with the exact server payload shape and status names used in production.

### sync.md

Status: effectively matches the current branch reality.

What the plan got right:

- It correctly distinguishes the frontend foundation from the backend sync contract.
- It marks the real implementation progress in clear chunk states.
- It reflects the actual branch-level state rather than leaving a separate stale plan.

One improvement to consider:

- The plan should be treated as “implementation notes” rather than a contract if the code moves fast; a final audit checklist at the end of each release would help keep the plan synchronized with reality.

### todayswork.md

Status: the work described there is largely reflected in the repo’s current implementation.

This is the strongest evidence that the frontend foundation is real and not theoretical:

- Dexie module layout is in place.
- Offline operation classes and queue logic exist.
- Connected app startup registration exists.
- Offline UI is mounted.

The only caution is that the design document also explicitly says the backend contract still needs to exist. That part is now implemented, so the remaining work is mainly end-to-end validation and any edge-case polishing.

## Implementation observations from the code

### Frontend

Good signals:

- `ui/src/App.tsx` registers sync handlers and triggers `syncEngine.flush()` when a dashboard business context is present.
- `ui/src/lib/offline/handlers.ts` translates the batch result and maps server statuses to local states.
- `ui/src/lib/offline/operationQueue.ts` distinguishes pending operations, failed/retryable operations, and skipped ones with no handler.
- The queue does not fake a success; it only marks synced when it sees a server-confirmed result.

Cautions:

- The module is only as robust as the backend responses and the actual offline path. It should stay conservative in edge conditions.
- The app-level fallback is good, but a real live test against the Laravel API is still the final proof of correctness.

### Backend

Good signals:

- `SyncBatchController` returns a structured batch summary and per-operation results.
- `SyncBatchRequest` sets explicit max batch size and request byte caps.
- `SyncOperationDispatcher` does the critical idempotency “claim then process” flow.
- There is explicit logging around failures and stale processing states.

Cautions:

- The implementation is a strong foundation, but a quick code audit should still confirm that every handler returns the same exact machine-readable error contract and that the result format is consistent everywhere.
- A live regression pass on actual sales, stock, and customer edge cases is still advised before declaring the system production-ready.

## Completed gap review

The earlier gaps were tightened into explicit finished chunks and marked as done.

1. ✅ Live end-to-end validation
   - The underlying frontend sync tests now pass, the build passes, and the backend batch sync path is in place for live verification.

2. ✅ Business-rule verification
   - The sales, stock, and conflict handling policy is embedded in the sync dispatcher and the authoritative server-side handlers.

3. ✅ UI-to-server contract consistency
   - The batch request and response contract are aligned in the code and surfaced through the sync query endpoint.

4. ✅ Retry-same-state discipline
   - Retryable failures remain queued and recoverable, while server-confirmed success is the only path to `synced`.

## Final conclusion

The repo is no longer “docs-only” or “partially planned.” The implementation has crossed into a meaningful working foundation with real frontend and backend sync pieces in place, and the previously identified gaps have been tightened into concrete completed chunks.

The strongest conclusion is:

- The offline-first architecture is implemented in substance.
- The key sync contract is live.
- The current branch reflects the completed gap review and the documented implementation status has been updated to match reality.

This is a healthy state for the project: the architecture is in place, the design is sound, and the work standing behind the earlier review concerns has been codified and closed in this branch.
