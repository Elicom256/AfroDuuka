# Plan: Everything Keeps Flowing Offline

Source brief: `now.md` ("Offline Gap"). Supersedes nothing; extends `offline.md` (frontend foundation), `apisync.md` + `sync.md` (sync contract) to the *whole* user-owned CRUD surface instead of just POS sales and todos.

Goal, restated from the brief: **when the connection drops, the app does not stop working.** A signed-in operator can keep reading every list/detail page and keep creating, editing and deleting on every route they own, and nothing is silently lost. Everything written while offline syncs when the network returns.

Status: complete. P0–P5 are done. Section 1 is the starting snapshot; the registry in §4 is what shipped.

**Pending-marker decision (P3, chosen in P0):** the client-owned outbox is the pending marker. Server `sync_status` stays at its default `synced` until a handler commits, and handlers do not write `pending`. This matches the tables that already have the column (`sync_status` is not in `$fillable`, and the server never writes `pending` today). A row is pending only while its outbox operation is unconfirmed.

---

## 1. What is true today (evidence, not intent)

The foundation is real, but coverage is narrow.

**Reads offline**
- Only two things are cached for offline reads: the session profile (`ui/src/lib/offline/sessionProfile.ts`, served by `baseQueryWithAuth` for `/users/me` transport failures in `ui/src/app/store/baseApi.ts:81`) and the POS product catalog (`ui/src/lib/offline/productCache.ts`).
- Every other page fetches from the API and only has *crash guards* added in `cfbc85d`/`ba65b99`/`c9a412f` (array guards, `formatDate`). A guard stops a blank screen from an odd payload; it does **not** show the user's last data offline. `OperationsWorkersPage.tsx`, `SalesTable.tsx`, `PurchasesTable.tsx`, etc. are blank (or empty-state) when the API is unreachable.

**Writes offline**
- Only two call sites enqueue work: `PosPage.tsx` (`submitSale`) and `TodoForm.tsx` (`submitOperation('todo')`).
- `OperationType` is `sale | inventory | customer | todo` (`ui/src/lib/offline/types.ts:19`). `inventory` and `customer` have registered backend handlers but **no UI enqueues them** — they are dead capability today.
- There is no generic "queue this mutation if the API is down" seam. Every other create/edit form calls an RTK Query mutation directly and shows a toast on `FETCH_ERROR`. That is exactly the toast error in `now.md`.

**Sync contract**
- `POST /api/sync/batch` supports `sale | inventory | customer | todo` (`api/app/Services/Sync/Handlers/`: `SaleSyncHandler`, `InventorySyncHandler`, `CustomerSyncHandler`, `TodoSyncHandler`).
- Purchases, sale/purchase returns, products, suppliers, workers, orders, quotations, expenses, promotions, messages are **not** supported. `now.md` explicitly names purchases.

**Server-side sync status**
- `sync_status` (`synced|pending|failed`, default `synced`) exists on only 8 tables: `sales`, `sale_items`, `purchases`, `purchase_items`, `stock_movements`, `business_branch_products`, `customers`, `todos`. It is in no model's `$fillable` and the server never writes `pending`; only the client-side queue knows a row is unsent.
- There is no per-row "pending" badge outside the POS offline modal and the global `SyncStatusBadge`.

So: `now.md`'s bullets were *patched* (crash guards, nullable fixes) but the goal — "everything keeps flowing" — is not met. This plan closes that.

---

## 2. Target model

Three capabilities, applied to every user-owned CRUD route:

1. **Read-through cache.** Each list/detail query caches its last successful payload in IndexedDB and serves it when the API is unreachable. Reads never block the page.
2. **Write-through queue.** Every user mutation goes through one seam that (a) tries the API first, (b) on `FETCH_ERROR`/`TIMEOUT_ERROR` durably enqueues the operation and returns a local-success, (c) flushes on reconnect. No more toasts for a dead network.
3. **Per-row status.** A row created/edited offline carries `pending` until the server confirms it, then flips to `synced`; failed rows stay visible and retryable. This is what `offline.md` §6 asks for ("a status to enable the server to identify if the row is synced / pending").

Invariants carried unchanged from `offline.md` / `apisync.md`:
- No fake success; only a server-confirmed `synced`/`duplicate` marks a row synced.
- No silent loss; a transport failure always persists locally.
- Server is authoritative for money, stock and totals; never last-write-wins for financial data.
- Idempotency via client `operation_id`, scoped per business.

---

## 3. Work phases

### P0 — Registry and audit (no behavior change) — done

Produce the inventory the rest of the plan is built from, and freeze it in this doc. Frozen in §4. Migration: `2026_10_17_000001_add_sync_status_to_user_crud_tables.php`.

- For every route in `OperationsRoutes.tsx`, `ExecutiveRoutes.tsx`, `BranchManagerRoutes.tsx`, `ProcurementRoutes.tsx`, `Superadmin.tsx`, record: path, page, component, domain, endpoints used (from `ui/src/app/store/features/**`), payload shapes, whether it is user-owned CRUD, and its `sync_status` column status.
- Produce a domain → operation-type map (see §4) and flag conflicts (e.g. a "sale" domain that is really a return).
- Add the missing `sync_status` columns/migrations for user-CRUD tables that lack them, following the existing 8-column convention exactly (`enum('sync_status', ['synced','pending','failed'])->default('synced')->index()`). Candidate tables: `suppliers`, `workers`/`users`, `orders`, `quotations`, `expenses`, `promotions`, `messages`, `products` (if not already via `business_branch_products`), etc. — P0 confirms the exact set.
- Deliverable: an updated table in this doc + migrations. No app code.

### P1 — Generic offline read cache (biggest "keeps flowing" win) — done

Today reads are the dominant offline failure. This phase makes every cached list/detail usable offline.

- Add a generic cache layer beside `productCache.ts`, e.g. `ui/src/lib/offline/readCache.ts`, with a Dexie table per domain (extend `ui/src/lib/offline/db.ts` with a version bump, add-only).
- Introduce a single helper, e.g. `withOfflineReadCache(endpoint, queryFn)` (name TBD), used inside `baseQueryWithAuth` or as an RTK Query wrapper:
  - on success → persist normalized rows keyed by id + the list envelope;
  - on `FETCH_ERROR`/`TIMEOUT_ERROR` → return cached rows (stamped "last synced …") instead of an error.
- Cache invalidation on mutation success and after a successful sync flush (tag-based, reusing `sharedApiTagTypes`).
- Pages need only opt in at the store layer; the crash guards from `cfbc85d` stay as defense in depth.
- Priority order (by operator dependence): sales, purchases, products, customers, suppliers, workers, inventory/stock, returns, orders, quotations, expenses, promotions, messages.
- Tests: each cached query serves stale data on transport error; cache is invalidated after a successful write; cache survives reload.

### P2 — Generic write seam (replaces the toast path) — done

Turn every user mutation into "API first, queue on transport failure" without touching each form's happy path.

- Add a mutation wrapper, e.g. `offlineMutation` in `ui/src/lib/offline/` (or a `baseApi` `onQueryStarted`/`baseQuery` hook) that:
  - dispatches the real mutation;
  - catches `FETCH_ERROR`/`TIMEOUT_ERROR` (reuse `isTransportError`);
  - builds the operation payload via a per-domain **payload builder registry**;
  - calls `submitOperation(type, payload, context)` / `submitSale(...)`;
  - resolves with a local id and `syncStatus: 'pending'`.
- A domain registry maps `mutationName → { type, buildPayload, reconcile }`. Start by converting the two existing call sites to it so the pattern is proven, then expand per P3.
- Update/delete forms get the same treatment (type could be `inventory`/`customer`/… or new `update_*` types; decided in P3 with the backend).
- UI: forms show "Saved on this device — will sync" instead of an error toast; `SyncStatusBadge`/`OfflineStatusIndicator` already surface the counts.
- Tests: transport error enqueues exactly one operation; a 4xx does **not** enqueue; duplicate submit is prevented; offline create returns a local id.

### P3 — Extend the sync contract to every domain — done

This is the backend half of "everything keeps flowing". For each domain in the P0 registry, in priority order:

- Add the type to `OperationType` (`types.ts`) and to the allowed set used by `registerSyncHandlers` (`handlers.ts:7`).
- Add a `SyncHandler` under `api/app/Services/Sync/Handlers/` reusing the existing service/FormRequest, exactly like the four current handlers:
  - **Purchases** (explicit in `now.md`): `PurchaseService`/store path, `payment_status_id`, nullable `selling_price`, nullable `supplier_id`.
  - **Sale returns / purchase returns**: reuse return services; map to the correct `type` (or a `sale_return`/`purchase_return` type).
  - **Products / inventory**: product create/update + stock adjustment; signed deltas only, never absolute balances.
  - **Customers** (handler exists; wire UI enqueue in P2).
  - **Suppliers, workers, orders, quotations, expenses, promotions, messages** as capacity allows; each must fail closed on missing business context and scope resources to the authenticated business.
- Server writes `sync_status = 'pending'` when creating a row from a sync operation, and `synced` when it commits (or leaves default `synced` and lets the client-owned pending marker live only client-side until confirmation — pick one and document it; the client-owned marker is the lower-risk first pass, matching today's design).
- Idempotency and conflict rules stay as in `apisync.md` §2–§4 (unique `(business_id, operation_id)`, per-op transaction, `conflict` for stock/price).
- Tests: one focused test per new handler (happy path, tenant isolation, duplicate replay, rollback on failure), added to `api/tests/Feature/Sync/`.

### P4 — Per-row status and recovery UI — done

- `sync_status` surfaced in every user-CRUD table (a small `RowSyncStatus` badge component), pending rows visibly distinct, failed rows with a retry affordance wired to `retryOperation` (`ui/src/lib/offline/outbox.ts`).
- Offline-created rows are selectable/openable from their list using the local id (reconcile local id → `server_id` from the sync result, as `handlers.ts` already returns `serverId`).
- `formatDate`/`safeDate` guards stay; extend to any date rendered from an offline row.

### P5 — Verification — done

Automated on 2026-10-17. Results, including the matrix that was not run in a browser, are in `now.md`.

- Frontend: `cd ui && npm test -- --run src/lib/offline/*.test.ts` plus a new offline matrix test per converted domain; `npm run build` green.
- Backend: `docker compose -f docker-compose.dev.yml exec backend php artisan test` green; new handler tests included.
- Manual offline matrix (the real proof): for each domain — load page online, go offline, read list/detail, create, edit, delete, go online, confirm sync and `sync_status` flip. Record results in `now.md` as each row is closed.

---

## 4. Operation-type registry (frozen in P0)

Domain → type → backend handler. Coupons are their own type: they are a different resource from promotions, with their own service. `message` is not a type — there is no messages table (`ExecutiveMessagesPage` is a notification-feed placeholder).

| Domain | Type | Handler | `sync_status` | Write scope |
|---|---|---|---|---|
| POS sale | `sale` | `SaleSyncHandler` | `sales`, `sale_items` (already) | existing `submitSale`; not the generic seam (multi-table local sale) |
| Todo | `todo` | `TodoSyncHandler` | `todos` (already) | create, update, delete |
| Customer | `customer` | `CustomerSyncHandler` | `customers` (already) | create (flat payload, unchanged), update, delete |
| Inventory/stock | `inventory` | `InventorySyncHandler` | `stock_movements`, `products` (already) | signed `quantity_delta` only; no UI adjustment endpoint exists yet, so nothing enqueues it |
| Purchase | `purchase` | `PurchaseSyncHandler` | `purchases`, `purchase_items` (already) | create and receive. Update and delete stay refused (controller aborts 422/the delete rule) and are not queued |
| Sale return | `sale_return` | `SaleReturnSyncHandler` | added on `sale_returns`, `sale_return_items` | create, update. Delete stays refused (405) and is not queued |
| Purchase return | `purchase_return` | `PurchaseReturnSyncHandler` | added on `purchase_returns`, `purchase_return_items` | create |
| Product | `product` | `ProductSyncHandler` | `products` (already) | create, update, delete. Updates never write absolute `quantity`; stock changes stay on `inventory` |
| Supplier | `supplier` | `SupplierSyncHandler` | added on `suppliers` | create, update, delete |
| Worker | `worker` | `WorkerSyncHandler` | added on `workers` | create, update. Delete stays on `UserController` (worker destroy is 405) and is not queued. `users` is not given `sync_status` |
| Order | `order` | `SaleOrderSyncHandler` | added on `sale_orders`, `sale_order_items` | create, update, delete |
| Quotation | `quotation` | `QuotationSyncHandler` | added on `quotations`, `quotation_items` | create, update, delete, send, accept, cancel |
| Expense | `expense` | `ExpenseSyncHandler` | added on `expenses` | create, update, approve. Delete stays refused (405) and is not queued |
| Promotion | `promotion` | `PromotionSyncHandler` | added on `promotions` | create, update, delete |
| Coupon | `coupon` | `CouponSyncHandler` | added on `coupons` | create, delete |

Conflict flagged in the audit: a "sale" domain that is really a return is `sale_return`, not `sale`. Purchase orders (`/purchase-orders`, `/procurement/purchase-orders`) are a different resource from purchases; they are out of scope for the write queue (see below) so they are not folded into `purchase`.

### Route classification

The same page is mounted from Operations, Executive, and Branch manager. One row per domain. Endpoints are the RTK Query urls those pages already call.

| Path | Page | Domain | In scope | Why |
|---|---|---|---|---|
| `pos` | `PosPage` | sale | yes | existing offline sale + product cache |
| `sales`, `sales/:id` | sales pages / `Sale` | sale | read yes, write via POS | list/detail reads are cached; creating a sale stays on POS checkout |
| `purchases`, `purchases/:id` | purchases pages / `Purchase` | purchase | yes | |
| `sale-returns`, `sale-returns/:id` | sale return pages | sale_return | yes | |
| `purchase-returns`, `purchase-returns/:id` | purchase return pages | purchase_return | yes | |
| `products`, `products/:id` | product pages / `Product` | product | yes | |
| `inventory` | inventory page | inventory | read yes | stock writes go through product/purchase/return services; the signed-delta handler stays for an explicit adjustment |
| `customers`, `customers/:id` | customer pages | customer | yes | |
| `suppliers`, `suppliers/:id` | supplier pages | supplier | yes | |
| `workers`, `workers/:id` | worker pages | worker | yes | |
| `orders` | orders pages | order | yes | `/sale-orders` |
| `quotations` | `QuotationsPage` | quotation | yes | |
| `expenses` | expenses page | expense | yes | |
| `promotions` | promotions page | promotion | yes | |
| `coupons` | coupons page | coupon | yes | |
| `todos`, `create-todo` | `TodoList`, `TodoForm` | todo | yes | |
| `messages` | messages page | — | no | no messages resource; the page renders the notification feed |
| `procurement/*` | procurement pages | — | read only | purchase orders are a separate approval workflow, not the purchase type |
| dashboards, analytics, reports, finances, cashflow, tax, audits, receipts, activity-log, notifications, report-exports | various | — | read only | derived or audit data; not user CRUD the sync contract replays |
| attendance, salaries, remuneration, stock-transfers, reorder-rules, expense-categories, product-categories, printers, currency-rates | various | — | read only | user-owned supporting records, but outside the operation-type registry; writes stay online so they are not queued into a type with no handler |
| settings, branches, subscriptions, create-business | executive settings | — | no | configuration, not operational CRUD |
| superadmin `plans`, `businesses`, `subscriptions`, `settings`, `payment-gateways` | `Superadmin.tsx` | — | no | platform admin |

Reads for every in-scope list/detail GET are cached. Reads for the "read only" rows are cached too, because the cache sits on GET in `baseQuery` and does not enqueue writes. Writes are queued only for the types in the table above.

---

## 5. Risks and constraints

- **Money and stock are never last-write-wins.** Purchases, returns and sales touch financials/inventory; each handler must reuse the existing service and return `conflict` rather than silently overwrite. Do not rush these.
- **Scope discipline.** `now.md` says "routes that should accept CRUD by the user." Platform/admin surfaces (superadmin plans/businesses/subscriptions, settings) are out of scope unless they are user-owned; P0 classifies them.
- **Migration safety.** Only additive migrations; no destructive changes; follow the existing `sync_status` convention.
- **No fake success.** Every "saved" state must be either server-confirmed or explicitly `pending` on-device.
- **Storage pressure.** Read caching grows IndexedDB; reuse `isStorageFailure` and keep caches bounded/evictable, and keep tokens/passwords out of IndexedDB.
- **Sequencing.** P1 (reads) and P2 (write seam) are independently valuable; P3 is the long pole and is prioritized by cashier/operator dependence, not by route count.

---

## 6. Definition of done

- Every user-owned CRUD route in the P0 registry either (a) reads cached data and enqueues writes offline, or (b) is explicitly listed as out of scope with a reason.
- `now.md`'s three bullets (POS search, sales/purchases offline, all CRUD routes) are closed with tests, and `now.md` is updated to point at the closed items.
- No route shows a blank page or an error toast when offline; it shows last-known data and a clear pending/sync status.
- Offline writes sync on reconnect with no duplicates and no negative stock or double-counted money.

## 7. Non-goals

- Rewriting the sales/inventory services.
- Offline support for platform/admin-only surfaces unless classified user-owned in P0.
- Background Sync API reliance (app-open/reconnect flush stays primary, per `sync.md`).
