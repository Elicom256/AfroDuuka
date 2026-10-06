# DuukaFlow — Outstanding Findings

**Source:** `checked.md` (pre-launch review, 2026-10-01 @ `b718ad0`)
**Re-verified:** 2026-10-06 @ `oct` — every finding below was checked against current code before being kept here.

## How to read this file

The review that produced `checked.md` is no longer accurate. Every finding was checked
against the codebase rather than trusted, and the review's own labels were not always
right — it marked #2 as FIXED with stale details, and counted `staff` as a missing role
when the correct fix was to delete it.

- **✅** — resolved. Kept in place rather than deleted, so it stays visible what was found
  and what was decided about it.
- **⬜** — still open. **P1 is complete; P2 is the next section to work.**
- Items that were *partly* addressed say so explicitly. Nothing is marked fixed on the
  strength of a plausible-looking change; each one was re-run against current code.

**Headline:** P0 resolved, P1 complete, P2 partly done.
Suite is green at **737 backend / 747 frontend**.

**P2:** 6 backend + 5 frontend items closed, 6 left open. Two of the closed ones were the
review misdiagnosing — see the dark-mode entry.

---
## ✅ Resolved — earlier work

Everything fixed before the current pass. Kept as a table because these were closed by
earlier commits rather than item by item here; the P1 section below carries the detail
for what was done in this pass.

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
| — | 36 failing tests | Now 725 pass, 0 fail |
| — | Role gate missed `BranchManager` | `RolePermissions::normalise()` + `hasAnyRole()`; 23 policies and the finance gate converted |
| — | `cash_balance` always 0 | Derived from the ledger on read; stored `running_balance` column dropped |
| — | Todos 404'd outside Executive | Route + nav in every dashboard tree |
| — | Till receipt differed from receipt page | Both render one shared `ReceiptView` |

---

## P1 — High — ✅ complete

### ✅ 13. Roles seeded with nowhere to go — **DECIDED, not a defect**
`editor`, `supplier` and `customer` stay seeded deliberately: a business needs to record
the people and companies it buys from and sells to, and being able to contact a supplier
depends on that data existing. The missing piece is the portal UI, not the role. Recorded
in `lib/roles.ts` so it reads as a decision rather than an oversight.

**To do when there is appetite:** build the supplier/customer portals.

### ✅ 14. The `staff` role — **DONE**
Every person in a business is staff, so a separate role split one job across two
vocabularies, and no `staff` row was ever seeded — its tree and six pages were
unreachable dead code. Deleted: `StaffDashboard.tsx`, `StaffSidebar`, the six pages, the
`staff` key in `ROLE_DASHBOARD_TREE`, and the `AppRoutes` entry. Checked first that no
other tree imported any of its components.

### ✅ 18. `ProcurementRoutes` and `SuperadminRoutes` had no auth guard — **DONE**
Both now wrap their trees in `ProtectedRoutes`, matching `OperationsRoutes`. Each tree
carries its own requirement instead of inheriting it from `AppRoutes` role branching,
which is what made it "one refactor from exposed".

### ✅ 19. Destructive actions deleted with no confirmation — **DONE**
`ConfirmDeleteButton` added (ShadCN `AlertDialog`, matching the two screens that already
had one) and applied to every delete trigger: 17 direct calls plus 12 native `confirm()`
guards across 21 files — products, categories, tax rates/payments/categories, expense
categories, coupons, salaries, workers, customers, suppliers, printers, payment gateways,
currency rates, reorder rules, notifications, held POS sales, todos.

Two triggers were bare `<Trash2 cursor-pointer>` icons with no button semantics at all;
they are now real buttons inside the dialog. `BranchSetup`'s `remove(index)` is excluded
on purpose — it drops a form field, not a record.

`deleteConfirmation.test.ts` holds the line: it reads every page file and fails on a
delete trigger with no confirmation. Source-level on purpose — the failure being
prevented is a new unguarded trigger in some of ~230 files, which no rendered test would
notice. A page counts as covered when it delegates `onDelete` to a child, because that
child is itself a file the test reads.

**Known cosmetic issue:** the wrapped JSX has uneven indentation in a few multi-line
triggers. Valid and compiling; this repo has no formatter configured.

### ✅ 20. Query hooks ignored `isError` — **DONE**
Fixed at two points rather than in 178 components:

- `apiErrorMiddleware` (store) reports any request that failed with no handler, so a 500
  can no longer render as "No sales found". Silent on 401/403, which session and
  permission handling own.
- `ErrorBoundary` at the root, so a render throw is a readable message instead of a
  blank page (this also closed P2-25's boundary half).

**Known trade-off, asserted in the test rather than hidden:** a component that catches and
toasts still lets the raw request fail, so the middleware can fire alongside its own
message. Doing better needs the rejection on the action, which RTK Query does not expose.

### ✅ 22. `/api/health` leaked internals — **DONE**
Returns only `ok`/`error`; the raw driver exception goes to the log. Unreachable route, so
nobody on the internet gets the host, port, database name or auth detail.

### ✅ 24. No-op deletes returned 200 — **DONE**
`BusinessBranchController`, `WorkerController` and `StockMovementController` now answer
**405** and say why. The branch one was hiding a data-loss hazard: `sales`, `purchases`
and ~25 other tables cascade on `business_branch_id`, so the moment that empty method
gained a body it would have deleted a branch's trading history for real.

---

## P2 — Medium

### Backend
- ✅ **`PurchaseItem` had no `SoftDeletes`** while its migration has the column — trait
  added. A deleted line is now recoverable and stays out of the normal listing; before,
  it was indistinguishable from one that never existed.
- ✅ **`SaleItemService` low-stock alert read the pre-sale quantity** — the alert fired on
  stock that was about to change and stayed silent about stock that had. Now assessed on
  the quantity the sale leaves behind, matching `PosService::checkout`, which decrements
  before checking. A product at 11 with a threshold of 10, selling 3, lands on 8: nothing
  fired before.
- ✅ **`SaleItemService` branch matching** — already present in the service (added with
  the `lockForUpdate()` change) but untested. `SaleItemBranchMatchingTest` now pins it,
  and pins the mirror case too: `EffectiveBranchScope` refuses a branch outside the
  caller's scope with 403 before the product lookup is reached, so there are two
  independent guards.
- ⬜ **`stock_movements.reference_type/id` are unconstrained polymorphic strings.**
  Deleting a sale orphans movements and never reverses stock. This is a **schema
  decision, not a code fix**: real foreign keys per reference type, or a reversal step on
  sale deletion. Both change behaviour, so it needs a deliberate choice.
- ⬜ **Jobs never wrap work in `BusinessContext::run()`.** Not done deliberately: all five
  jobs already filter on `business_id` explicitly in every query, so there is no current
  cross-tenant defect — it is a footgun for future jobs. Wrapping them is a defensive
  refactor with real regression risk and no bug fixed, so it wants its own focused pass
  with the behaviour pinned first.

### Frontend
- ✅ **Dark-mode contrast — the review's diagnosis was wrong.** It reported
  `--muted-foreground` failing at 3.37:1. Measured, that token is **8.18:1** on the dark
  background and 7.63:1 on the dark card. The review had compared the *light* value
  (0.48) against the dark background, which is 3.11:1 — close to its number and the
  origin of the mistake. Changing the passing token would have "fixed" nothing while
  making the light theme worse, so it was left alone.
- ✅ **The real leak was 46 hardcoded greys.** `text-gray-500` and friends with no `dark:`
  override: **3.87:1 on the dark card**, passing in light mode — the same symptom, a
  different cause. Replaced with `text-muted-foreground` across 21 files.
  `themeContrast.test.ts` now fails on any hardcoded grey without a dark override.
- ✅ **`PosPage` restored held sales with `stock: 9999`.** A hardcoded number told the
  cashier the shelf was effectively empty, so a held sale resumed after the stock had gone
  looked sellable right up to the point it failed. Now reads the live quantity the held
  sale already carries, falling back to `0` so a missing relation blocks instead of
  inventing availability.
- ✅ **`SuperadminSettingsPage` restated operational values as literals** — "Operational",
  "UGX", "Africa/Kampala", a support address, none connected to the value shown. Now read
  from the platform; anything with no stored value says so rather than inventing one.
- ✅ **No error boundary.** Added at the root as part of P1-20.
- ⬜ **No route-level code splitting.** The whole bundle ships as one chunk.
- ⬜ **178 hardcoded `any`.** Deliberately not swept. The review wants a type-safety pass;
  doing it as 178 file edits risks breaking working screens for no runtime gain. The
  right lever is a lint budget (`no-explicit-any` as a warning with a ratchet, not off),
  which is a decision about tooling rather than code.
- ⬜ **POS is desktop-only.** A design change, not a bug fix.
- ⬜ **Accessibility gaps.** 57 of 61 tables lack `overflow-x-auto`; 119 `<Input>` lack
  `id`; 181 `<Label>` lack `htmlFor`; hand-rolled dialogs with no `role="dialog"` or focus
  trap. Large and mechanical; not started.

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
