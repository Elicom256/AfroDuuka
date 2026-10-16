# Offline-First Frontend Foundation — Implementation Plan

Source brief: `offline.md`. Scope for this pass: **frontend foundation + local persistence only**. No invented Laravel sync endpoints, no fake sync success.

## Progress

- ✅ Chunk 1 — Dexie persistence + durable outbox core (+tests)
- ✅ Chunk 2 — Connectivity monitoring hooks/services (+tests)
- ✅ Chunk 3 — PWA config via vite-plugin-pwa + SW registration
- ✅ Chunk 4 — Offline-aware operation layer + POS/CRUD integration
- ⬜ Chunk 5 — `sync_status` columns (migrations)
- ⬜ Chunk 6 — Offline status UI (indicator + sync badge)
- ⬜ Chunk 7 — Sync engine interfaces + hooks
- ⬜ Chunk 8 — Final verification (lint/test/build) + deliverables


## 1. Codebase facts this plan is built on

**Stack & build**
- React 19 + Vite 8 + TypeScript 6; tests via Vitest (jsdom) in `ui/vitest.config.ts`; lint `eslint . --max-warnings=931`; build `tsc -b && vite build`.
- `vite-plugin-pwa@^2`, `dexie@^4.4.6`, `dexie-react-hooks@^4.4.0` are installed in `ui/package.json` and present in `ui/node_modules`.
- No service worker, no PWA manifest, no `registerSW` call anywhere in `ui/src`. `ui/public` contains only `afroduuka.png`; `ui/index.html` favicon is `/afroduuka.png`. (No 192/512/maskable icons exist — see risks.)
- Prod is a single nginx (`ui/nginx.conf`) that serves the SPA and proxies `/api/` to the Laravel container; TLS is terminated upstream. No cache headers currently set for `sw.js`/`manifest.webmanifest`.

**State & data layer**
- One RTK Query slice: `baseApi` (`ui/src/app/store/baseApi.ts`), `baseUrl = import.meta.env.VITE_BASE_URL`, Bearer token via `getToken()` (`ui/src/lib/session.ts`, localStorage key `token`).
- Every feature injects endpoints via `baseApi.injectEndpoints` and re-exports hooks (`ui/src/app/store/features/**`). Cache refresh is **purely declarative tags** (`invalidatesTags`/`providesTags`); `onQueryStarted` is used **nowhere**.
- Store wires `baseApi.middleware` + `authListenerMiddleware` + `apiErrorMiddleware` (`ui/src/app/store/app/store.ts`).
- Mutation error handling convention: `try/catch` around `.unwrap()` + sonner `toast` (global `<Toaster>` in `ui/src/App.tsx`).
- POS is `ui/src/app/pages/dashboards/shared/pos/PosPage.tsx`. Sale creation = `useCheckoutMutation()` → `POST /pos/checkout`, body `{ items:[{product_id,quantity,unit_price,discount}], payments:[{method,amount}], customer_id, note, sale_id? }`; success returns `{ message, sale }` (receipt modal consumes `sale`).
- Relevant write endpoints (outbox scope): POS `POST /pos/checkout`, `POST /pos/sales/hold`, `DELETE /pos/sales/held/{id}`; sales `POST /sales/branch-sales/`, `PUT /sales/branch-sales/{id}`; products `POST /products/`, `PUT /products/{id}` (stock adjust reuses this), `DELETE /products/{id}`; customers `POST/PATCH/DELETE /dashboard/customers/…`; todos `POST/PUT/DELETE /users/todos/…`; stock transfers and purchases (adjacent).
- Most mutation payloads are typed `any` (only todos and `receivePurchase` have shapes) — the outbox needs its own typed payloads.

**Backend (for interface prep / missing deps)**
- Auth: Sanctum bearer tokens (`POST /api/users/login`).
- Health endpoints exist and are unauthenticated: `GET /api/health` (DB+cache probe, 503 on failure) and `GET /api/up`. `VITE_BASE_URL` points at `/api`, so reachability check = `GET ${VITE_BASE_URL}/health`.
- Tenant scoping columns are `business_id` and `business_branch_id`; `products`/`purchases` carry only `business_branch_id`; `BaseModel` auto-stamps them.
- **No general idempotency mechanism** (no `Idempotency-Key`, no client uuid). Only `stock_movements.movement_key` is unique/idempotent. This is the key missing backend contract.
- Candidate tables for `sync_status`: `sales`, `sale_items`, `products`, `stock_movements`, `customers`, `purchases`, `purchase_items`, `todos`.

## 2. Proposed file layout

New module (follow existing `src/lib` convention; hooks currently live in `src/hooks`):
- `ui/src/lib/offline/db.ts` — Dexie DB + versioned schema.
- `ui/src/lib/offline/types.ts` — `SyncOperation`, `OperationType`, `SyncStatus`, entity/payload types.
- `ui/src/lib/offline/outbox.ts` — enqueue/list/update/retry/remove helpers (transactional).
- `ui/src/lib/offline/connectivity.ts` — online/offline + health probe + sync state store.
- `ui/src/lib/offline/operationQueue.ts` — offline-aware operation layer / sync-engine interface (no real network flush yet).
- `ui/src/lib/offline/index.ts` — public exports.
- `ui/src/hooks/use-connectivity.ts` and `ui/src/hooks/use-pending-operations.ts` (`dexie-react-hooks` `useLiveQuery`).
- `ui/src/components/offline/OfflineStatusIndicator.tsx` (+ `SyncStatusBadge.tsx`) using existing `components/ui/*` primitives.

Tests: `ui/src/lib/offline/*.test.ts`, `ui/src/components/offline/*.test.tsx`.

## 3. Workstreams (mapped to offline.md)

### 3.1 Dexie persistence + durable outbox (§3)
- Versioned DB `afroduuka-offline`, schema v1: `operations` (outbox), plus local entity stores needed for atomic writes (`sales`, `saleItems`, `stockAdjustments`, `customers`, `todos`).
- `operations` fields: `operationId` (PK, `crypto.randomUUID()`), `type` (`sale|inventory|customer|todo`), `payload` (serialized), `createdAt`, `status` (`pending|syncing|synced|failed`), `retryCount`, `lastError`, `businessId`, `branchId`, `serverId?`, `syncedAt?`.
- `outbox.ts`:
  - `enqueue(ops)` — single Dexie transaction; **rejects duplicate `operationId`** (`bulkAdd` / uniqueness guard); durable before returning "saved locally".
  - `listByStatus`, `markSyncing`, `markSynced`, `markFailed(inc retry)`, `retry`, `remove`.
  - Atomic sale write: `db.transaction('rw', sales, saleItems, stockAdjustments, operations, …)` so sale + line items + local stock adjustment + outbox entry commit together (no partial transaction).
- Upgrades: keep `db.version(n).stores(...).upgrade(...)` pattern; document add-only policy.
- Handle `QuotaExceededError` / storage failures → surface via toast + status indicator.
- Never store passwords/tokens; only business payload data.

### 3.2 Connectivity monitoring (§4)
- `connectivity.ts`: subscribe to `window` `online`/`offline`; probe `${VITE_BASE_URL}/health` via `fetch` on mount, on reconnect, and on a throttled interval (single in-flight request, no tight polling); derive `{ online, apiReachable, pendingCount, syncing, lastSyncedAt }`.
- Treat browser events as signals only; API reachability is authoritative-ish but never invents an endpoint (uses the real `/health`).
- Clean up listeners/timers on unmount.
- `use-connectivity.ts` wraps it for React.

### 3.3 PWA via vite-plugin-pwa (§2)
- Add `VitePWA({ registerType: 'prompt', ... })` to `ui/vite.config.ts` **without removing** `react()`, `tailwindcss()`, aliases, server config.
- `includeAssets: ['afroduuka.png']`; `manifest` with `name/short_name "AfroDuuka"`, `theme_color`/`background_color` from existing theme, `display: 'standalone'`, `start_url: '/'`, icons using the existing `/afroduuka.png` (see risks re: 192/512/maskable).
- `workbox`: `globPatterns` for shell/static assets; `navigateFallback: '/index.html'`; `navigateFallbackDenylist: [/^\/api\//]`; explicit **no runtime caching for `/api`**, no caching of authenticated responses or mutations; no second SW.
- Update strategy: `registerType: 'prompt'` + register via `virtual:pwa-register` in `ui/src/main.tsx`; defer/decline update while a POS session is active so an update never interrupts a sale. Confirm `sw.js` is served no-cache through nginx (adjust `nginx.conf` if needed).
- Do not claim full offline capability from app-shell caching alone.

### 3.4 Offline-aware operation layer + POS integration (§5)
- `operationQueue.ts` exposes a stable interface: `submit(operationType, payload, context) → { queued, localId }`, plus sync-engine stubs (`flush()`, status subscriptions) with **no fake success**.
- Integration approach: keep the existing online path intact. In `PosPage.handleCheckout` (and the sales/products/customers/todos forms), when `apiReachable` is false or the request fails at transport level:
  - persist locally via the atomic transaction (§3.1) and report "saved on this device, pending sync";
  - never silently drop the sale;
  - distinguish locally-saved vs server-confirmed in the UI (status badge + receipt/modal copy).
- When online, keep calling the existing RTK Query mutations unchanged. Do **not** replace API submission with a mock.
- Because sales need server-side idempotency + inventory semantics, implement the safe foundation and **document that end-to-end offline sale sync is not complete** until the backend contract exists.

### 3.5 `sync_status` schema (§6, backend)
- Edit the create migrations for `sales`, `sale_items`, `products`, `stock_movements`, `customers`, `purchases`, `purchase_items`, `todos` to add `sync_status` (proposed: `string`/enum default `'synced'`, nullable-safe) **and** add one new additive migration for already-migrated databases (editing create migrations alone only affects fresh installs — flag this).
- Local rows created offline start `pending`; become `synced` after the sync engine confirms. Server inserts carrying an offline origin get `pending` then flip to `synced`.
- No destructive migration changes; no changes to unrelated columns.

### 3.6 Offline status UI (§6)
- `OfflineStatusIndicator`: online/offline, pending count, sync-in-progress, failed count; accessible labels (aria-live), minimal footprint, no banner that disrupts layout.
- `SyncStatusBadge` reused in POS and in CRUD tables/rows to show `pending|syncing|synced|failed`.
- Follow existing component/styling conventions (shadcn `components/ui`, Tailwind tokens); do not redesign dashboard/POS layout.

### 3.7 Sync engine interfaces (§7)
- Define clean types now: stable idempotency key (= `operationId`), per-operation status, retry/backoff, partial-failure, tenant/branch isolation, server-confirmed completion, recoverable failures.
- Explicitly **no** last-write-wins for sales/stock/payments/finance.
- Provide app-open/reconnect flush fallback; do not rely solely on Background Sync API.

### 3.8 Tests & quality (§8)
- Add `fake-indexeddb` as a **required** dev dependency (jsdom has no IndexedDB; Dexie cannot run without it) and wire it in `ui/src/test/setup.ts` or a per-file setup.
- Cover: enqueue durability, duplicate `operationId` rejection, status transitions, atomic multi-store transaction rollback, connectivity transitions, pending-count hooks, status indicator rendering.
- Run `npm run lint`, `npm run test`, `npm run build` and record actual results.
- Avoid unrelated refactors and extra dependencies.

## 4. Files to create / modify (summary)

Create: `ui/src/lib/offline/{db,types,outbox,connectivity,operationQueue,index}.ts`, `ui/src/hooks/{use-connectivity,use-pending-operations}.ts`, `ui/src/components/offline/{OfflineStatusIndicator,SyncStatusBadge}.tsx`, offline tests, one additive `sync_status` migration in `api/database/migrations/`.

Modify: `ui/vite.config.ts`, `ui/src/main.tsx`, `ui/index.html` (theme-color/title), `ui/nginx.conf` (SW/manifest cache headers, if needed), `ui/src/test/setup.ts`, `ui/package.json` (+`fake-indexeddb`), POS + sales/products/customers/todos components for enqueue + badges, and the eight create-migration files for `sync_status`.

## 5. Risks / open questions / backend dependencies

1. **No real icon sizes**: only `afroduuka.png` exists. Installability needs 192/512 + maskable. Plan references only existing paths; generating icons is a separate asset task (won't invent paths).
2. **Editing existing create migrations** won't upgrade already-deployed DBs — the additive migration is mandatory. Confirm the intended `sync_status` values/default.
3. **No backend idempotency contract**: offline replay of sales/stock needs an `Idempotency-Key`-style guarantee (reuse `movement_key` model). Until then, offline sales are queued locally only — no end-to-end sync claim.
4. **`sync_status` semantics**: clarify whether it is server-authored per row or only a local marker; brief implies server rows show pending→synced.
5. **jsdom lacks IndexedDB** → `fake-indexeddb` required (new dev dep; treated as necessary, not optional).
6. **PWA update vs active POS**: `prompt` strategy + POS-session guard needed to avoid interrupting a sale.
7. **Customer tag gap**: `addCustomer` invalidates `CustomersAPI` but branch reads provide `BranchCustomersAPI` — pre-existing refresh gap the offline layer must not worsen.

## 6. Deliverables (filled in after implementation, per offline.md §9)

1. Files created/modified. 2. PWA + SW behavior. 3. IndexedDB schema + outbox API. 4. Hooks/UI added. 5. Test/lint/build commands and actual results. 6. What works offline vs what needs backend. 7. Remaining Laravel integration points (idempotency, sync endpoints, conflict resolution, inventory reconciliation).
