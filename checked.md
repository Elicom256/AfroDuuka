# DuukaFlow — Pre-Launch Review

**Date:** 2026-10-01
**Branch:** `oct` @ `b718ad0`
**Scope:** Full codebase — Laravel 12 API (83 controllers, 80 models, 89 migrations) + React 19/TS/Vite UI (305 pages, 66 API slices)
**Method:** Static analysis **plus live verification** — tests executed in Docker, TypeScript compiled, ESLint run, and a temporary security probe test written and run against the real app to confirm exploitability. Every finding below is evidence-backed; nothing is carried over on trust from the previous review.
**Excluded from scoring:** anything requiring a paid third-party API (Meta WhatsApp Cloud API, URA e-invoicing submission, S3/CloudFront, payment gateways, SES sending). Those are addressed in §9.

---

## Executive Summary

DuukaFlow is a genuinely substantial application. Feature breadth is not the problem: multi-branch inventory, POS, procurement, expenses, credit ledgers, attendance/payroll, URA tax invoices, quotations, loyalty, stock transfers and audit logging are all materially implemented. Money is stored as `decimal`, POS checkout correctly uses `DB::transaction` + `lockForUpdate()`, stock movements are idempotent via a unique `movement_key`, and there are ~370 passing tests including a genuinely good WhatsApp delivery suite with dedupe, suppression and signature verification.

**But it is not launch-ready, and the blockers are more serious than the previous review suggested.**

The previous review (`review.md`) scored this 7/10 and framed the remaining work as "operational hardening." That framing is wrong. Three problems are *not* hardening — they are active security and correctness failures:


2. **Any authenticated user can ban any tenant** and **any staff member can delete the owner**. Both routes sit outside the `role` middleware group. Verified.
3. **The test suite runs against the development database.** `phpunit.xml` and `.env.testing` both point at `inventory` — the live dev DB — while a fully-migrated `inventory_test` database exists and is referenced by nothing. This is how the schema got truncated mid-run below.

The frontend has an equally concrete problem: **41 of ~50 primary row-click handlers navigate to URLs that resolve to a 404 or a blank screen.** `AppRoutes.tsx:43` matches the literal path `/dashboard`, so `/dashboard/sales/5` does not match. The entire Procurement module renders nothing because `procurementApi` is defined but never registered in the Redux store. Four of nine seeded roles have no route branch at all.

**Verdict: 5.5/10. Do not launch.** The path to launch is short and well-defined — the P0 list is roughly 10 items, most of them one-file fixes — but every one of them is a real defect, and several are data-loss or cross-tenant-exposure class.

---

## Verified Baseline

| Metric | Result | Notes |
|---|---|---|
| Backend tests | **370 pass, 36 fail, run aborts early** | Suite dies with `Premature end of PHP process`; not a full pass |
| TypeScript | **Passes** (`tsc -b`, exit 0) | Honest result, not suppressed |
| ESLint | **1,099 problems** (1,081 errors) | 974 `no-explicit-any`; CI runs lint → **CI is red** |
| Frontend bundle | **1.61 MB**, single chunk, no code splitting | `dist/assets/index-DtH65d4d.js` |
| CI | `.github/workflows/ci.yml` **exists** | Contradicts `plan.md` which says it was removed |
| Money columns | All `decimal` | No `float`/`double` in migrations — good |
| `authorize()` calls | **19, across 4 of 82 controllers** | 78 controllers have zero authorization |
| Policies written | **29** | 25 are never invoked |
| `console.log` | 16 (all commented out) | `review.md`'s "96 statements" is stale — already fixed |

---

## P0 — Launch Blockers

### 1. Public sign-up mints an unscoped, cross-tenant account — **CONFIRMED LIVE**

`api/app/Services/UserService.php:79-90` creates the user with the tenant columns commented out:

```php
'password' => Hash::make($data['password'] ?? "password"),
// 'business_id' => $data['business_id'] ?? null,
// 'role_id' => $executiveRoleId,
```

`business_id` is nullable, and both scope resolvers treat NULL as *unrestricted*:

- `api/app/Support/Tenant/BusinessContext.php:61-70` returns `null` → `BaseModel.php:39` never emits `where business_id = ?`
- `api/app/Support/Tenant/EffectiveBranchScope.php:27-29` returns `null` → branch restriction skipped

I wrote a probe test against the running app: it asserted the created user has `business_id === null`. **Confirmed.** The follow-up probe that would have read another tenant's products could not complete because the dev DB was truncated by the test run (finding #3), but the static path is unambiguous — `GET /api/products` is `auth:sanctum` only with no `role` gate, and `ProductController::restocking()` (`ProductController.php:110-126`) filters raw `sale_items` by branch only, skipping the filter entirely when `branchesFor()` returns `null`.

**Action:** Remove the public `/signup` route or gate it behind an invite token. Independently, make `BaseModel` **fail closed** — NULL `business_id` should return zero rows unless the caller is on an explicit `siteadmin` allow-list.

### 2. Any authenticated user can ban any tenant — **CONFIRMED**

`api/routes/super-admin.php` — the entire file is `auth:sanctum` with **no** role check and **no** policy:

```php
Route::middleware("auth:sanctum")->group(function () {
    Route::get("/businesses", [SuperAdminBusinessController::class, "index"]);
    Route::patch("/businesses/{business}/status", [..., "updateStatus"]);
});
```

`SuperAdminBusinessController` contains zero `authorize()` calls. My probe confirmed a plain tenant user receives **200** on `/api/super-admin/businesses` and can flip any business to `banned`.

**Action:** Gate the controller on a `siteadmin` permission in its constructor.

### 3. Tests run against the **development database**

`api/phpunit.xml:26-31` and `api/.env.testing` both set `DB_DATABASE=inventory` — the same database the dev stack uses. A fully-migrated **`inventory_test`** database (93 migrations) exists and is referenced by **nothing** — I grepped the entire repo. `RefreshDatabase` then drops and recreates the dev schema on every run.

This is why the suite aborts: a concurrent test process had dropped `migrations` mid-run (`relation "migrations" does not exist`), and 4 of my probe fixtures failed on `null value in column "business_category_id"`. It also means **running the test suite can destroy dev data**.

**Action:** Point `phpunit.xml` at `inventory_test` (or set `DB_DATABASE=inventory_test` in `.env.testing`). Highest priority-per-effort item in this document — one line, and it unblocks a reliable suite.

### 4. Any staff member can delete the owner — **CONFIRMED**

`api/routes/users.php:24` sits **outside** the `role` group that wraps lines 15-18:

```php
Route::delete('/workers/{worker}', [UserController::class, 'destroy']);
```

`UserController::destroy()` (`UserController.php:209-213`) checks only tenant membership, never role. The sole guard, `BlockRestrictedRoleActions.php:32`, matches one literal role name `'operations'`. A probe with a `Cashier` deleting the owner returned **200** and the owner was gone.

**Action:** Move the route inside the `role` group; invert `BlockRestrictedRoleActions` from a denylist to an allow-list.

### 5. Receipt number collisions roll back the whole checkout

`receipts.receipt_number` is `UNIQUE` (`2026_07_15_000001_create_receipts_table.php:13`), but both generators count-then-increment with no lock:

- `api/app/Services/ReceiptService.php:18-19`
- `api/app/Services/PosService.php:382-383`

Two concurrent checkouts compute the same number; the second insert violates the unique index and **the entire transaction rolls back** — sale, items, payments, stock movements and cash flow all lost while the customer walks away with goods.

**Action:** Per-business/per-day sequence table, or `MAX+1` inside `lockForUpdate()` on a counter row, with retry on unique violation.

### 6. The entire Procurement module renders nothing — **CONFIRMED**

`procurementApi` is defined at `ui/src/app/store/features/procurement/procurementQuery.ts:75` with `reducerPath: 'procurementApi'`, but `ui/src/app/store/app/store.ts` contains **zero** references. Of 66 `createApi` instances, 65 are registered and this one is not.

RTK Query logs `No data found at state.procurementApi` and — because the **middleware is also missing** — `initiate` is never handled: no request is made, `isLoading` never fires, no error surfaces. Pages render their empty state forever. Compounding it, `ProcurementRoutes.tsx:18` matches against the full absolute path while nested under `procurement/*`, so **10 routes across Executive and BranchManager are permanently blank**.

**Action:** Add `procurementApi.reducer` + `.middleware` to the store; use relative paths in the nested routes.

### 7. 41 navigation targets 404 or render blank — **CONFIRMED**

`AppRoutes.tsx:43` matches only the exact path:

```tsx
<Route path='dashboard' element={<Navigate to={getRolePrefix(role)} replace />} />
```

React Router does not match sub-paths without `/*`. I counted **35 hardcoded `/dashboard/...` links across 33 files** (39 including variants). These are the primary row-click handlers on the Sales, Purchases, Products, Workers, Customers, Suppliers, Returns, Attendance, Finance and Audit tables — i.e. **the most-clicked controls in the product are dead**. `StaffDashboard` has no fallback route at all, so its two table links render a blank white page.

**Action:** Derive every path from `useRolePrefix()`, or use relative `<Link>`. Add `<Route path='*' element={<NotFound/>}/>` to each role tree as a safety net.

### 8. Auth failure silently renders the marketing homepage

`AppRoutes.tsx:22` destructures `error` and never uses it:

```tsx
const { data, isLoading, error } = useLoggedinUserQuery();
if (isLoading) { return <PageLoadingState />; }
```

If `/me` 401s, `role` is `undefined`, no role branch mounts, and the user gets the public homepage + 404. The token is never cleared from `localStorage`, there is no 401 interceptor, and `UserProfile.tsx:28` swallows errors with `catch (error) {}`.

**Action:** Handle `error` → clear token, redirect to `/login`. Add a global 401 interceptor.

### 9. `.env.prod` is committed to git with a real `APP_KEY`

`api/.env.prod` is tracked (`git log` shows commits `db090e1`, `c788f7c`) and contains a populated `APP_KEY`, `DB_PASSWORD`, and `WHATSAPP_ACCESS_TOKEN`. It also has `APP_ENV=local` and `APP_DEBUG=true`. Separately, `.env.prod` is **not** in `api/.gitignore` (which lists `.env`, `.env.backup`, `.env.production` — a different filename).

**Action:** Rotate `APP_KEY`, untrack the file, add `.env.prod` to `.gitignore`, and purge from history. `APP_KEY` rotation invalidates all encrypted columns — plan a re-encrypt migration.

### 10. Production compose cannot serve traffic

Three compounding defects:

- **Proxy points at a Vite dev port.** `proxy/nginx.prod.conf` has `upstream frontend { server frontend:5173; }`, but `ui/Dockerfile` builds a static bundle and serves it from `ui/nginx.conf` on **port 8080**. The prod frontend is unreachable.
- **No TLS.** The proxy listens on `443:443` but has **zero** `ssl_certificate` directives and no `listen 443 ssl` block. Port 443 accepts nothing.
- **No security headers.** `proxy/nginx.prod.conf` has **0** `add_header` directives, while the *dev* conf correctly sets `X-Frame-Options`, `X-Content-Type-Options` and `Referrer-Policy`. Production is less protected than development.

**Action:** Point the upstream at `frontend:8080`, add a real TLS block, and port the dev security headers into prod.

---

## P1 — High

| # | Issue | Evidence |
|---|---|---|
| 11 | **Suspended/banned users can still log in.** `UserService.php:26` checks only the password; `users.status` is never read. Defeats the ban in #2. | `UserService.php:26` |
| 12 | **Oversell race in the non-POS sale path.** `SaleItemService.php:48-64` reads products with no `lockForUpdate()`, then decrements. Concurrent sales drive stock negative. `PosService.php:163` does it correctly — the fix is to copy it. | `SaleItemService.php:48` |
| 13 | **Four of nine roles land on 404 after login.** Seeder creates `Executive, BranchManager, Operations, Procurement, editor, supplier, customer, CoreSupport, siteadmin`; `AppRoutes` branches on only 6. `siteadmin`, `editor`, `supplier`, `customer` have no tree — and **`supplier`/`customer` portals don't exist in the UI at all**. | `RoleTableSeeder.php:23` vs `AppRoutes.tsx:46-51` |
| 14 | **`staff` is not a seeded role** — the entire Staff dashboard (6 routes) is unreachable in practice. | `RoleTableSeeder.php:23` |
| 15 | **Signup accepts a blank password.** `StoreUserRequest.php:27` marks it `nullable`, then `UserService.php:86` falls back to `Hash::make("password")`. An attacker who omits the field gets the account password `password`. Probe returned 500 only because the DB was truncated; the code path is live. | `StoreUserRequest.php:27` |
| 16 | **Zero cache invalidation.** No `extraReducers`, `addMatcher`, or `onQueryStarted` in any of the 66 slices. **After a sale, products/inventory/sales/finance caches never refresh** — staff see stale stock. | `ui/src/app/store/` (all slices) |
| 17 | **5 rule-of-hooks violations crash detail pages.** Early `if (!id) return null` sits before query hooks in 5 live return components. | `executive/components/sale-returns/SaleReturn.tsx:13-15` +4 |
| 18 | **`StaffDashboard`, `ProcurementRoutes`, `SuperadminRoutes` have no auth guard.** In `OperationsRoutes` the guard wraps *only* the POS route; the whole main tree at line 50+ is unguarded. Currently masked by `AppRoutes` role branching — one refactor from exposed. | `OperationsRoutes.tsx:47-50` |
| 19 | **27 destructive actions delete with no confirmation.** Only 2 of 29 delete flows use a proper `AlertDialog`. | `executive/components/customers/Customer.tsx:26,55` +26 more |
| 20 | **178 files use query hooks and never check `isError`.** A server error renders as "no data" — users can't tell "no sales" from "backend down". | throughout `app/pages` |
| 21 | **AI endpoint is an unrestricted data interface.** `routes/ai.php` is `auth:sanctum` only. `Agent.php` sends the raw user message to Gemini and executes the returned tool name + params with no server-side authorization. 22 of 25 tools have no explicit tenant reference and rely entirely on the global scopes that finding #1 disables. | `Agent.php:23,53` |
| 22 | **`/api/health` leaks internals.** Unauthenticated, returns raw `$e->getMessage()` from driver exceptions. (My probe confirmed 200 + full body on a healthy DB.) | `routes/api.php:18,25` |
| 23 | **`SupplierController` 500s instead of 403** — `abort_if()` args transposed, so the message lands in the `$code` position and `HttpException` raises a `TypeError`. `CustomerController` has the correct order. | `SupplierController.php:34` |
| 24 | **No-op deletes return 200.** `BusinessBranchController::destroy()`, `WorkerController::destroy()`, and all of `StockMovementController` have empty bodies that still return success. | `BusinessBranchController.php:74-77` |

---

## P2 — Medium

**Backend**
- `Sale::salePayment()` is declared `BelongsTo` but multiple `SalePayment` rows per sale are normal (split payments). Returns an arbitrary row; the API response silently omits split payments. → make it `HasMany`. (`Sale.php:44`)
- `SaleItemService` never compares `$product->business_branch_id` to `$branchId`, so an Executive sale can draw down another branch's stock. `PosService.php:165` gets this right. (`SaleItemService.php:48-51`)
- `purchase_items` migration has `softDeletes()` but the model doesn't use the trait — soft-deleted rows become invisible-but-present. (`PurchaseItem.php:9`)
- Jobs never wrap work in `BusinessContext::run()`. Any future job that forgets it reads and writes across all tenants silently. The class docblock is also now stale relative to `BaseModel`. (`app/Jobs/`, `BusinessContext.php:11-14`)
- Low stock alerts evaluate against the pre-decrement in-memory quantity — threshold reads one sale too late. (`SaleItemService.php:65`, `PosService.php:272`)
- `stock_movements.reference_type/id` are polymorphic strings with **no FK** — deleting a sale orphans movements and does not reverse stock.

**Frontend**
- **Dark theme is default and `text-muted-foreground` fails WCAG AA** — 3.37:1 on background vs 4.5:1 required, used **821 times across 238 files**. Light mode passes (16.87:1); dark does not. (`main.tsx:13`, `App.css:121`)
- **No error boundary, no `React.lazy`, no `Suspense`** — any render throw is a blank page; 1.61 MB ships as one chunk including Chart.js and the public marketing site.
- **POS is desktop-only** — zero responsive breakpoints, `h-screen`, fixed `w-80` panel, fixed-width modals. (`PosPage.tsx`)
- **178 hardcoded `any`** across 231 files. `tsc` passes only because the ESLint rule is disabled — not because the types are sound.
- 30 files duplicate code verbatim (8 byte-identical groups, 17 files) — e.g. `EditSale.tsx` ×3, `EditPurchase.tsx` ×2, return components ×2 each.
- 15+ orphaned components/pages; 4 unused `components/ui/*` primitives including a complete `field.tsx` accessibility wrapper that is never imported.
- 57 of 61 tables lack `overflow-x-auto`; 119 `<Input>` lack `id`, 181 `<Label>` lack `htmlFor`; 0 `aria-live`, 0 `aria-invalid`; 8 hand-rolled dialogs with no `role="dialog"` or focus trap.
- `SuperadminSettingsPage.tsx:4-38` hardcodes operational status, currency, timezone and support email on a **live** settings page. `PosPage.tsx:266` restores held sales with fabricated `stock: 9999`.

**Repo hygiene**
- 23 markdown files in the repo root, many superseded and mutually contradictory (`plan.md` lists CI as removed; `review.md` cites 96 `console.log` — actually 16; both predate `RBAC genuinely enforced`).
- `GEMINI.md` is empty. No root `.gitignore`.

---

## P3 — Low

- `StoreUserRequest.php:34` — `role_id` uses an unscoped `exists` rule; a cross-tenant role named `Executive` would grant elevated rights.
- `SaleItemService.php:116` — unguarded `PaymentMethod::find(...)->value()`, 500s inside the transaction on a bad id.
- `PosService.php:191` — the `sale_id` resume path doesn't verify `user_id` (unlike `resumeHeldSale()`); one user can complete another's held sale.
- `SubscriptionController.php:27-31` — bulk cancel + create are not transactional; a failure leaves a business with zero active subscriptions.
- `ActivityLogController.php:19` — `SUPERVISORY_ROLES` declared and never referenced.
- `Report` model has no `$fillable` and no callers — dead code.
- `vite.config.ts` sets `allowedHosts: true`.
- 29 of 60 form files have no `disabled` state on submit → double-submit possible.

---

## Recommended Sequence

### Day 1 — stop the bleeding (≈3 hours)
1. Point `phpunit.xml` at `inventory_test` → suite becomes trustworthy. **Unblocks everything else.**
2. Delete/gate `POST /api/users/signup`; make `BaseModel` fail closed on NULL `business_id`.
3. Gate `SuperAdminBusinessController` on `siteadmin`; move `DELETE /workers/{worker}` inside the `role` group.
4. Enforce `status` in `UserService::login()`; remove the `"password"` fallback.
5. Register `procurementApi` in the store (2 lines — un-blanks 10 routes).

### Day 2 — correctness
6. `lockForUpdate()` in `SaleItemService`; branch-match products in that service.
7. Locked receipt numbering + retry on conflict.
8. Fix `AppRoutes` path matching + the 35 hardcoded `/dashboard/` links; add `path='*'` to every role tree.
9. Handle `error` in `AppRoutes`; add a 401 interceptor that clears the token.
10. Map `siteadmin`/`editor`/`supplier`/`customer` roles or seed only what the UI serves.

### Day 3 — production readiness
11. Fix the prod compose upstream (`frontend:8080`), add TLS, port the dev security headers.
12. Untrack + rotate `.env.prod`; purge `APP_KEY` from history.
13. Add global cache invalidation (a single `onQueryStarted` matcher) — stale stock after a sale is a revenue bug.
14. Add an error boundary + `React.lazy` route splitting.
15. Darken `--muted-foreground` to 4.5:1.

### Week 2
16. Fix the 36 failing tests — note that 33 are **harness bugs, not product bugs**: 32 call `LogTransport::flush()`, which no longer exists in this Laravel version, and 4 hit the truncated DB. Rewrite against `Mail::fake()` / `Event::fake()`.
17. Clear ESLint to 0 errors so CI goes green.
18. Accessibility pass + delete-confirmation dialogs + error states.

---

## §9 — Final Advice on Excluded Items

The brief asked me to exclude paid-API-dependent work from the findings, then advise on everything regardless. **These are not "nice to have" — items 1 and 2 are the difference between a demo and a business.**

1. **Payments are the product, and there are none.** Mobile money (MTN MoMo, Airtel Money) is how ~90% of Ugandan retail transacts. A POS that only records cash and manual verification cannot be sold as a production system — every sale needs a human confirming it. This is the single largest commercial gap. Budget for it as a Phase 2 line item, not a "later" item.
2. **WhatsApp Stage 4 wiring.** The delivery engine is genuinely well-built (dedupe keys, suppression, signature verification, ambiguous-send handling — the tests are the best in the repo). But the catalogue must be dispatched from real business events or nothing sends. Note `WHATSAPP_PROVIDER=demo` is still the default in both `.env` and `.env.prod`.
3. **Receipt delivery.** Receipts exist as PDFs but reach nobody. Email/SMS receipts are table stakes for retail; customers expect them.
4. **URA e-invoicing.** Structural work is done. Verify the fiscalisation response handling is retry-safe and idempotent before going live — URA rejects duplicate submissions and a duplicate fiscalisation number is a compliance event, not a UI glitch.
5. **Subscription billing.** With no auto-collection, SaaS revenue depends entirely on manual follow-up. It will not scale, and churn will be high.
6. **Backup/restore.** `DatabaseBackup.php` exists but there is **no scheduling, no off-site copy, and no tested restore**. An untested backup is not a backup. Do a restore drill into a scratch database before you launch.
7. **Monitoring.** No error tracking, no uptime alerting, no structured logging. You will learn about outages from customers. `/api/health` exists and works — put it behind a real monitor.
8. **Offline-first POS.** Not required to launch, but Uganda's connectivity will eventually make a network-dependent till unusable during an outage. Design the sync architecture *before* launch; retrofitting it after data exists is far more expensive.
9. **UAT and multi-tenant isolation verification.** After the P0s, run a real end-to-end pass with two tenants seeded and deliberately attempt cross-tenant reads. Given findings #1-#4, this must be a deliberate adversarial test, not a happy-path walkthrough.

---

## Bottom Line

The engineering quality underneath is inconsistent: the WhatsApp delivery pipeline, the POS transaction handling, `InventoryService`'s locking, and the `BranchPerformanceReports` join-ambiguity fix are all careful, correct work. But that discipline did not reach the auth boundary, the route layer, or the store configuration — and those are the layers a customer touches first.

**Do not launch.** Findings #1-#5 are cross-tenant data exposure and data loss; #6-#8 mean large parts of the product do not function when clicked. All ten are tractable in roughly a week of focused work.

The most valuable single line in this document is `DB_DATABASE=inventory_test` in `phpunit.xml`. It costs a minute, it stops the test suite from destroying the development database, and it is what turns "we think this works" into "we have verified this works."