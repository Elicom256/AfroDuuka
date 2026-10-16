# AfroDuuka — Combined Sync Implementation Plan

Combined brief: `apisync.md` (Laravel sync contract + frontend sync-engine wiring merged from `offline.md` §7).
Frontend foundation: already delivered — see `todayswork.md`. This plan covers the undone half: the backend idempotent sync API and wiring the existing outbox to it.

Status:

- ✅ Chunk 1 — Idempotency store (migration + model)
- ✅ Chunk 2 — Batch endpoint + dispatcher
- ✅ Chunk 3 — Type handlers
- ⬜ Chunk 4 — Security and tenant isolation
- ⬜ Chunk 5 — Backend tests
- ⬜ Chunk 6 — Frontend syncQuery endpoint
- ⬜ Chunk 7 — Register handlers and flush
- ⬜ Chunk 8 — Frontend tests
- ⬜ Final verification + deliverables

## 1. Facts this plan is built on

**Frontend (done, `ui/`)**
- Dexie outbox: `ui/src/lib/offline/db.ts` (stores `operations`, `sales`, `saleItems`, `stockAdjustments`), `outbox.ts` (`enqueueSale()` is one atomic `rw` transaction over all four tables), typed in `types.ts` (`OperationType = sale | inventory | customer | todo`, `SyncStatus = pending | syncing | synced | failed`).
- Sync engine: `ui/src/lib/offline/operationQueue.ts` — `SyncEngine` singleton, exponential backoff (10s base, 15min cap), `startSyncFallback()` flushes on app-open and on unreachable→reachable transitions. **No production `OperationHandler` is registered — `flush()` skips every operation.** This is the documented blocker.
- `SaleOperationPayload` already mirrors `POST /pos/checkout` exactly (`items[]`, `payments[]`, `customer_id`, `note`, `sale_id?`).
- Enqueue points: `ui/src/app/pages/dashboards/shared/pos/PosPage.tsx` `saveOffline()` (unreachable or transport error only) and `ui/src/app/pages/dashboards/executive/components/todos/TodoForm.tsx` (`todo`).
- API client: RTK Query, `ui/src/app/store/baseApi.ts`, `baseUrl = VITE_BASE_URL`, Bearer token from `@/lib/session`. Connectivity probes `GET {VITE_BASE_URL}/health`.
- Status UI exists: `ui/src/components/offline/OfflineStatusIndicator.tsx`, `SyncStatusBadge.tsx`.

**Backend (`api/`, Laravel 13 + Sanctum + PostgreSQL)**
- No `/api/sync` route, no `operation_id` idempotency anywhere. Only `stock_movements.movement_key` (md5) dedupes stock rows — retried checkout would create a second `Sale`.
- Sale creation: `PosController@checkout` → `PosService::checkout()` (line ~149): validation → receipt number → `DB::transaction` with `lockForUpdate` on products → `createSaleFromCart()` (sale, sale items, `decrement('quantity')`, `StockMovement::updateOrCreate` by movement_key, payments, cash flow, receipt). `InsufficientStockException` → 409.
- Route conventions: `routes/api.php` requires domain route files under `throttle:api`; `RequireBusiness` middleware runs globally after auth; routes declare `auth:sanctum` themselves.
- `sync_status` enum column (`synced|pending|failed`, default `synced`) already exists on `sales`, `sale_items`, `products`, `stock_movements`, `customers`, `purchases`, `purchase_items`, `todos` — but is in no model's `$fillable` and is never written by the server (server-created rows are synced by default; `pending` only originates on devices).
- Tests: PHPUnit 12 (`composer test` → `php artisan test`; `phpunit.xml` expects `docker compose exec backend php artisan test`), feature tests in `api/tests/Feature/` with `RefreshDatabase` + `Sanctum::actingAs`. Frontend tests: Vitest with `fake-indexeddb` (`outbox.test.ts`, `operationQueue.test.ts`, `syncEngine.test.ts`…).

## 2. Backend chunks

### Chunk 1 — Idempotency store
- New migration `create_sync_operations_table` + `SyncOperation` model.
- Fields: `id`, `business_id` (FK, indexed), `user_id` (FK, the authenticated actor), `operation_id` (uuid, client-generated), `type`, `status` (`processing | completed | failed`), `server_resource_type` / `server_resource_id`, `result` (json — enough to replay the original response), `error_code` / `error_message`, `attempts`, `processed_at`, timestamps.
- `unique(['business_id', 'operation_id'])` — database-enforced idempotency scope, matching the tenant model.
- Safe retry: `failed` rows are re-runnable (attempts incremented); `completed` rows replay `result` verbatim as `duplicate`; `processing` rows older than a stuck threshold are recoverable, never permanently wedged.

### Chunk 2 — Batch endpoint + dispatcher
- New `api/routes/sync.php`, required from `routes/api.php` under the existing `throttle:api` group, `auth:sanctum` + existing policies. *(Implemented; `RequireTillAccess` deliberately not applied — per-type authority is enforced in handlers.)*
- `sync_operations` carries `client_created_at` (audit only) in the create migration itself.
- `POST /api/sync/batch` → `SyncBatchController` + `SyncBatchRequest` (cap batch size, e.g. 50; per-op: `operation_id` uuid, `type` in `sale|inventory|customer|todo`, `payload` array, `created_at` for audit only).
- `Sync\OperationDispatcher`: one independent DB transaction per operation:
  1. Insert `SyncOperation` (`processing`) first — a unique violation means a duplicate; return stored `result` when `completed`.
  2. Run the type handler inside the same transaction; persist `result` + `completed` before commit.
  3. On domain/validation failure → rollback business effects, mark `failed` with a stable error code, return `failed`/`conflict` for that op only.
- Response envelope per op: `{operation_id, status: synced|duplicate|conflict|failed, server_id?, error?: {code, message}}`. Success is never reported before commit. One failed op never blocks the rest of the batch.

### Chunk 3 — Type handlers (reuse, don't duplicate)
- **Minimal refactor:** extract `PosService::checkout()`'s transaction body into a public method callable by both `PosController@checkout` and the sync `SaleHandler`, so pricing/totals/tax/stock math stays single-sourced.
- Sale handler: authorize branch against the authenticated business (existing scope helpers), validate items/payments, `lockForUpdate` products, stock check → insufficient stock returns a `conflict` result (`insufficient_stock`) instead of silently losing the op; create sale + items + payments + cash flow with existing services; `movement_key` derived with `operation_id` so a replay can't double-move stock even beyond the idempotency guard.
- Offline price policy: compare client `unit_price` against the server price — mismatch returns `price_mismatch` conflict for reconciliation; never trust client totals/profit/stock balances; server recomputes everything.
- Inventory handler: signed-delta adjustments only, authorized via the existing adjustment workflow, auditable `StockMovement` keyed by `operation_id`; never absolute client balances.
- Customer handler: reuse existing customer creation rules + validation; retried submissions dedupe through `SyncOperation`, not invented matching rules; return `server_id` for local-id reconciliation.
- Todo handler: reuse the existing todo creation path (frontend already enqueues `todo`).
- Touch model `$fillable`/casts only if the server must write `sync_status`; default `synced` already covers server-created rows.

**Completed 2026-10-16:** registered and implemented sale, inventory, customer, and todo handlers. Sale checkout reuses `PosService`, checks cached prices and stock, and includes the operation key in stock movement identity; inventory accepts signed deltas through `InventoryService`; customer and todo creation reuse their existing FormRequests/services. Sync handlers fail closed without business context and explicitly scope branch/product/customer resources to the authenticated business. Focused feature coverage passes: 9 tests, 69 assertions (`docker compose -f ../docker-compose.dev.yml exec backend php artisan test tests/Feature/Sync/SyncBatchTest.php`).

### Chunk 4 — Security and tenant isolation
- Resolve `business_id` from the authenticated context only — payload tenant fields are ignored.
- Validate branch/product/customer ownership server-side; apply existing policies for sales, adjustments, customer creation; reject cross-tenant UUIDs.
- Batch and payload size limits; structured logs without credentials or tokens; operation IDs cannot replay across tenants (unique scope is per-business).

### Chunk 5 — Backend tests (PHPUnit)
New `api/tests/Feature/Sync/` covering apisync.md §9 items 1–12: happy path, unauthenticated 401, cross-tenant/branch rejection, duplicate replay returns original result, concurrent duplicates don't duplicate rows, sale rollback is atomic, insufficient stock/price conflict → `conflict`, partial batch isolation, invalid payload/type rejection, retry-after-timeout doesn't duplicate, customer retry safety, inventory audit rules.

## 3. Frontend chunks

### Chunk 6 — Sync API endpoint
- `ui/src/app/store/features/sync/syncQuery.ts`: `syncBatch` mutation → `POST /sync/batch` through `baseApi` (bearer auth, existing conventions). No second HTTP client.

### Chunk 7 — Register handlers and flush
- New `ui/src/lib/offline/handlers.ts`: `registerSyncHandlers()` registering `sale`, `inventory`, `customer`, `todo`; invoked at app startup (alongside the existing `useSyncFallback()` wiring in `App.tsx`).
- Handler behavior: submit pending+due-failed ops as a bounded batch; map results — `synced`/`duplicate` → `markSynced` (flips linked local row `syncStatus`), `failed`/`conflict` → `markFailed` with the server error code, transport error → leave `pending` for the existing backoff. 4xx validation failures are permanent failures, not endless retries.
- Update last-successful-sync time after a committed flush so `OfflineStatusIndicator` reflects it.

### Chunk 8 — Frontend tests (Vitest)
- Handler registration makes `flush()` submit instead of skip; per-op result → status mapping; partial batch (one failed, one synced); transport error leaves op pending; duplicate response marks synced without a second request.

## 4. Verification

- Backend: `cd api && composer test` (or `docker compose exec backend php artisan test` per `phpunit.xml`).
- Frontend: `cd ui && npm run test && npm run build`; `npm run lint` without regressing the documented backlog baseline.
- Manual E2E: enable offline → POS checkout (row shows pending) → back online → flush → sale exists server-side exactly once, local + server `sync_status` = synced, second flush sends nothing.

## 5. Definition of done

- `POST /api/sync/batch` live, documented (URL + request/response schema) per apisync.md §10.
- Replays/concurrent duplicates never create a second sale, movement, or cash-flow record (proven by tests).
- Stock can't go negative or double-move from offline sales; pricing conflicts surface instead of being silently overwritten.
- Registered production handlers: `syncEngine.flush()` actually transmits, and pending → synced is observable end-to-end.
- All apisync.md §9 tests green; deliverables reported with real command results and any remaining limitations.
