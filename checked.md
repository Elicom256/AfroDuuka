# DuukaFlow — Outstanding Findings

**Source:** `checked.md` (pre-launch review, 2026-10-01 @ `b718ad0`)
**Re-verified:** 2026-10-06 @ `oct` — every finding below was checked against current code before being kept here.

## How to read this file

The review that produced `checked.md` is no longer accurate. Findings were verified one by one against the codebase rather than trusted, and the ones that are genuinely done were removed from this file. Items that were *partly* addressed are listed with what is still missing, and nothing is marked fixed on the strength of a plausible-sounding change.

**Headline:** every P0 launch blocker is resolved. The remaining work is P1/P2 hardening and a handful of correctness gaps. Suite is green at **717 backend / 82 frontend**.

---

## ✅ Resolved — removed from this file

Verified fixed; no action needed. Kept here only so it is obvious they were considered.

| # | Finding | Now |
|---|---|---|
| 1 | Any authenticated user can ban any tenant | `super-admin.php` gated; `SuperAdminBusinessController` aborts; `BusinessContext::businessId()` fails closed |
| 2 | Tests ran against the dev database | `phpunit.xml` + `.env.testing` on `inventory_test`; `DB_URL` also forced empty so the Neon URL in the container env cannot be used |
| 3 | Any staff member could delete the owner | `DELETE /workers/{user}` sits inside the `role` group |
| 4 | Receipt number collisions rolled back checkout | `ReceiptNumberGenerator` uses a Postgres sequence, not count-then-increment |
| 5 | Procurement module rendered nothing | `procurementApi` reducer + middleware registered |
| 6 | 41 nav targets 404 / blank | `dashboard/*` splat route; catch-all `path='*'` in all 6 role trees |
| 7 | Auth failure silently rendered the homepage | `AppRoutes` handles `error`, clears dead tokens; `authListenerMiddleware` registered |
| 8 | `.env.prod` committed with a live `APP_KEY` | Untracked and gitignored |
| 9 | Production compose could not serve traffic | Upstream is `frontend:8080`; security headers ported; TLS documented as LB-terminated |
| 11 | Banned/suspended users could still log in | `UserService` checks `status` |
| 12 | Oversell race in the non-POS sale path | `lockForUpdate()` in `SaleItemService` |
| 15 | Signup accepted a blank password | `password` is `required\|min:6`; `"password"` fallback removed |
| 21 | `salePayment()` returned an arbitrary row | `salePayments()` is now `HasMany` |
| 23 | `SupplierController` 500'd instead of 403 | `abort_if` argument order corrected |
| 26 | `SUPERVISORY_ROLES` declared, never used | Now referenced via `RolePermissions::hasAnyRole()` |
| 27 | Held-sale resume skipped ownership check | Both `resumeHeldSale()` and the `checkout` `sale_id` path scope on `user_id` |
| 28 | `Report` model had no `$fillable` | Present and documented |
| 30 | `vite.config.ts` set `allowedHosts: true` | Restricted to localhost |
| — | 36 failing tests | 717 pass, 0 fail |
| — | Role gate missed `BranchManager` | `RolePermissions::normalise()` + `hasAnyRole()`; 23 policies and the finance gate converted |
| — | `cash_balance` always 0 | Derived from the ledger on read; stored `running_balance` column dropped |
| — | Todos 404'd outside Executive | Route + nav in all six dashboard trees |
| — | Till receipt differed from receipt page | Both render one shared `ReceiptView` |

---

## P1 — High

### 13. Four of nine roles still land on a dead end — **PARTLY DONE**
`ROLE_DASHBOARD_TREE` now maps `executive, branchmanager, coresupport, siteadmin, operations, procurement, staff` — `siteadmin` and `staff` are covered. But `RoleTableSeeder` still seeds `editor`, `supplier` and `customer`, and **none has a tree**; they hit `NoDashboardAccess`. The supplier/customer portals still do not exist in the UI.

**To do:** either build those portals or stop seeding the roles. Seeding a role with no destination is the actual defect.

### 14. `staff` is still not a seeded role — **OPEN**
The Staff dashboard has 6 working routes and no way to reach it in practice.

**To do:** seed `staff`, or delete the tree and its sidebar as dead code.

### 18. `ProcurementRoutes` and `SuperadminRoutes` have no auth guard — **PARTLY DONE**
`StaffDashboard` and `OperationsRoutes` now wrap their trees in `ProtectedRoutes`. **`ProcurementRoutes` and `Superadmin.tsx` still have zero** — they rely entirely on `AppRoutes` role branching, which is exactly the "one refactor from exposed" fragility the review warned about.

**To do:** wrap both in `<Route element={<ProtectedRoutes />}>`.

### 19. Destructive actions delete with no confirmation — **OPEN**
Only 3 files in the UI import `AlertDialog`. The review counted 27 destructive flows without one.

**To do:** add confirmation to delete/destroy actions. Start with anything that removes a record.

### 20. Query hooks still largely ignore `isError` — **PARTLY DONE**
31 files under `executive/` now handle `isError`; the review counted 178 files using query hooks.

**To do:** treat a server error as distinct from "no data" in the remaining components.

### 22. `/api/health` leaks internals — **OPEN**
Unauthenticated, returns raw `$e->getMessage()` from driver exceptions (`routes/api.php:23,30`).

**To do:** return a generic message plus a correlation id; keep the detail in the log.

### 24. No-op deletes return 200 — **OPEN**
`BusinessBranchController::destroy()` is still an empty body that returns success. Same shape in `WorkerController::destroy()` and `StockMovementController`.

**To do:** either implement the delete or return `405`/`501` so a caller is never told a delete succeeded when nothing happened.

---

## P2 — Medium

### Backend
- **`PurchaseItem` has no `SoftDeletes` trait** while its migration has the column — soft-deleted rows are invisible-but-present. (`PurchaseItem.php:9`)
- **`SaleItemService` never branch-matches products.** An Executive sale can draw down another branch's stock; `PosService` gets this right. (`SaleItemService.php`, product lookup)
- **Low-stock alerts read the pre-decrement quantity** — the threshold fires one sale late.
- **`stock_movements.reference_type/id` are unconstrained polymorphic strings.** Deleting a sale orphans movements and never reverses stock.
- **Jobs never wrap work in `BusinessContext::run()`.** Any future job that forgets it reads and writes across every tenant silently.

### Frontend
- **Dark theme is still the default and still fails WCAG AA.** `--muted-foreground: oklch(0.48 …)` in dark (`App.css:121`), used across hundreds of files. Light passes.
- **No error boundary, no route-level code splitting.** A render throw is a blank page, and the whole bundle ships as one chunk.
- **178 hardcoded `any` across 231 files.** `tsc` passes because the lint rule is off, not because the types hold.
- **POS is still desktop-only** — fixed panels, no breakpoints.
- **Accessibility gaps remain:** tables without `overflow-x-auto`, inputs without `id`, labels without `htmlFor`, hand-rolled dialogs with no focus trap.
- **`SuperadminSettingsPage` hardcodes operational values** on a live settings page; `PosPage` restores held sales with a fabricated `stock: 9999`.

### Repo hygiene
- **23 superseded markdown files in the repo root**, several mutually contradictory. `GEMINI.md` is empty; no root `.gitignore`.

---

## P3 — Low

- **`SaleItemService` payment-method lookup** — now guarded, but verify the 422 path is covered by a test.
- **`SubscriptionController` bulk cancel/create** — partially transactional; confirm the whole operation is atomic.
- **`StoreUserRequest.role_id`** — unscoped `exists` rule; a cross-tenant role named `Executive` would grant elevated rights.
- **Form submit double-submit** — many forms still lack a `disabled` state while in flight.

---

## Not fixed by design (deliberate, from the review's §9)

These are product decisions, not defects. Recorded so they are not mistaken for oversights.

1. **No mobile-money / card payments.** ~90% of Ugandan retail transacts on MoMo; a cash-only POS cannot be sold as a production system. Largest commercial gap.
2. **WhatsApp runs on the `demo` provider by default.** The delivery engine itself is solid; the catalogue is not yet dispatched from real events.
3. **Receipts are PDFs nobody receives.** No email or SMS delivery.
4. **URA e-invoicing response handling** needs a retry/idempotency check before go-live.
5. **No subscription auto-collection.** SaaS revenue depends on manual follow-up.
6. **Backups are untested.** `DatabaseBackup.php` exists with no schedule, no off-site copy, no restore drill.
7. **No error tracking or uptime alerting.** Outages will surface via customers.
8. **POS is not offline-capable.** Retrofitting sync after data exists is far costlier.
9. **No adversarial multi-tenant UAT.** Given the P0 history, this should be a deliberate cross-tenant access attempt, not a happy-path walkthrough.
