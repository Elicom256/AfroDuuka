# DuukaFlow — Product & Delivery Roadmap

> Living document. Generated from a full audit of the repo (Laravel API + React/TypeScript UI)
> on 2026-09-23. Update as work lands.

---

## ✅ 1. TL;DR — Current State

DuukaFlow is a multi-tenant inventory + retail management SaaS. The codebase is **feature-rich but
not launch-ready**. Raw numbers:

- **API**: Laravel 13 / PHP 8.3 / PostgreSQL, Sanctum auth. 74 migrations, ~70 models, 36 route files.
- **UI**: React 19 + TypeScript + Vite, Redux Toolkit/RTK Query, Tailwind v4 + shadcn. ~70 routes across Admin / Manager / Staff / Superadmin.
- **Broadly implemented already**: POS (checkout, hold/resume, split payments), sales, purchases, products, customers, suppliers, returns, expenses, tax (categories/rates/payments), loyalty, subscriptions/plans, receipts + PDF, price history, stock transfers, reorder rules, audits (product + financial), reports/analytics, WhatsApp demo layer, AI assistant (Gemini), finance/cash-flow.
- **85 tests** (13 files), concentrated on Product, Tax, POS, WhatsApp demo. Most modules untested.

**The biggest risks are not missing features — they are correctness, security (tenant isolation),
and production-readiness.** Only after hardening should the MVP ship.

---

## ✅ 2. MVP Definition (proposed cut line)

An MVP that can be **sold and demoed end-to-end** for a single retail business:

1. Admin can set up a business, branches, users (admin/manager/staff), products, suppliers, customers.
2. POS checkout works in production (real payments, receipts, stock deduction, cash-flow).
3. Purchases / stock in & out / transfers / returns work and reconcile.
4. Tax engine produces correct receipts/invoices.
5. Alerts & automation (WhatsApp/email/SMS) actually send (real provider, not demo).
6. Tenant isolation is watertight — Branch A can never see Branch B data.
7. Deployable on one server with HTTPS, backups, and monitoring.

**Explicitly OUT of MVP** (post-launch): warehouses/multi-location, serial & batch tracking,
payment reconciliation, mobile app, e-commerce storefront.

---

## ✅ 3. Where to Improve — Priority 0 (blockers, mostly backend)

### 3.1 Multi-tenant isolation is inconsistent — HIGHEST RISK

This is a multi-tenant SaaS; a broken tenant boundary is a data-leak / security issue.

- `BaseModel` only scopes/fills `business_id`; branch scoping is commented out (`api/app/Models/BaseModel.php:19-23`).
- **`Product` has no `business_id` at all** (only `business_branch_id`) and product `show/update/destroy` are unscoped `findOrFail` (`ProductController.php:41`). Cross-tenant product access possible.
- Controllers mix business-level and branch-level scoping inconsistently (index scoped, show unscoped).
- **Fix**: add tenant middleware/global scopes consistently, unify on branch-level scoping, add authorization policies that are actually invoked.

### 3.2 Role-based branch scoping is broken

- `SaleController.php:23`, `ReceiptController.php:16` compare `$user->role !== "admin"` — `role` is a **relation object**, not a string, so this is always true and branch scoping always applies (and the admin branch query uses an invalid `where("businessBranch", ...)`).

### 3.3 WhatsApp dedupe writes to a missing column (runtime error)

- `WhatsAppMessageLog` fillable + `ProcessWhatsAppNotificationJob:45` + `SendSubscriptionExpiryNotificationsJob` write `dedupe_key`, but **no migration adds that column**. The first scheduled WhatsApp job will throw a SQL error. (The `2026_09_22` tax tables had the same "bare migration" problem — audit all migrations for stub tables / model-migration mismatch.)

### 3.4 Queued job uses `Auth::user()`

- `CheckNotificationsJob` calls `Auth::user()` (null in queue context) and `Customer::whereHas('sales', ...)` where **`Customer` has no `sales()` relation** (`api/app/Models/Customer.php`).

### 3.5 Misc correctness bugs

- `BusinessBranch::users()` missing `return` (`BusinessBranch.php:18-20`).
- `UserService::signupUser` writes a non-existent `name` column.
- `StockTransferService::dispatch` matches the destination product **by `product_category_id` only** (`StockTransferService.php:49-61`), so stock moves to the wrong product across branches; `firstOrFail` throws when category is null.
- `BusinessService::create` nested role loop can duplicate roles across tenants.
- `ProductController` scoping + AI `ProductSearch` tool uses undefined `category` relation and products are not tenant-scoped.
- Policies exist (`ProductPolicy`, `SubscriptionPolicy`, ...) but **none are invoked** (`$this->authorize()` is never called).
- Left-in debug code: `dd()` in `UpdateCustomerRequest:28`, `StoreWorkerRequest:39`; commented-out `dd()`s in `SaleController`, `WorkerController`, `BusinessBranchController`, `UserService`.

### 3.6 Seeders are fragile & order-dependent

- Several seeders look up `testbusinessone@gmail.com` and throw `Business not found` unless the full seeder chain ran in exact order (`UserTableSeeder:26`, `RoleTableSeeder:22`, `ProductsTableSeeder:18`, `OrderSeeder:20`, `TaxSeeder:20`, `CouponSeeder:16`).
- **Fix**: make seeders resilient (skip + warn, or create their own fixture business) so `db:seed` is idempotent in any order.
- `UserTableSeeder:134` has a scoping bug (`Worker::with("user", fn...` — relation keyed wrong).

---

## ✅ 4. Where to Improve — Priority 1 (quality, frontend)

### 4.1 Broken / unreachable UI

- **Edit Sale / Edit Purchase dialogs are dead**: `setEditSale`/`setEditPurchase` are never invoked; `SalesTable`/`PurchasesTable` accept no `onEdit` prop → `<EditSale/>`/`<EditPurchase/>` unreachable.
- **Cross-area navigation bug**: `SalesTable.tsx:39` / `PurchasesTable.tsx:42` hardcode `/admin/sales/...` and `/admin/purchases/...` — a Manager or Staff user clicking a row lands in the admin area which won't render for their role.
- **5 stub pages** print "…will be implemented here": Manager Analytics, Manager Inventory, Staff Inventory, Staff Sales Overview, and Manager Notifications (always-empty).
- `AdminMessagesPage.tsx` is fully static placeholder; `SuperAdminSettingsPage.tsx` hardcoded; Manager/Staff dashboard cards show hardcoded values (`$1,234.56`, `+20.1%`, etc.).

### 4.2 Dead code / cleanup

- `admin-placeholder-pages.tsx` (unused), `manager/pages/Manager{Sale,Purchase}ReturnsPage.tsx` (unused duplicates of `manageradmin/` copies), `manager/components/products/TestProd.tsx` (referenced only in a comment).
- `posQuery.ts`, `receiptsQuery`, `aiQuery` registered in the store but underutilized / duplicated by `sales/pos` endpoints.
- ~30 debug `console.log`s in production paths (AppRoutes, Login, dashboards, settings).
- 404 `<NotFound/>` commented out in all role routers → authenticated users never see a 404.

### 4.3 Consistency

- Duplicated manager vs `manageradmin` return pages; unify.
- TS config disables `noUnusedLocals`/`noUnusedParameters`/`noImplicitAny` → build won't catch dead code. Re-enable strictness incrementally + add ESLint to `npm run build`.
- Orders naming inconsistency: `SaleOrder` maps to `orders` table; `routes/orders.php` still has `SaleOrderController::store` commented out while the UI (AdminOrdersPage) expects it.

---

## 5. Core 2026 Features That Are NOT Implemented (yet)

"Core in 2026" = what buyers of an inventory/retail SaaS now expect by default.

| Priority | Feature                                                   | Status today                                                                                                                                                                                                                            | Why it's core                                                                         | Suggested scope                                                                                                                                |
| -------- | --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| P0       | **Real payment collection (mobile money / card)**         | Only `PaymentGateway` CRUD + manual subscription payment verification. No provider integration.                                                                                                                                         | Retail POS must take MTN MoMo / Airtel Money / card. Manual verification won't scale. | Integrate 1 mobile-money provider + card gateway; POS split-payment already exists — connect it to gateways; webhook verification.             |
| P0       | **Customer communication (email/SMS/WhatsApp)**           | WhatsApp is **demo-mode** (`WHATSAPP_PROVIDER=demo`); no email/SMS.                                                                                                                                                                     | Digital receipts, reminders, renewals, marketing are table stakes.                    | Wire real provider(s) behind existing `WhatsAppService` abstraction; add email; finish the scheduled jobs (expiry, monthly report, low-stock). |
| P0       | **Product images & document attachments**                 | No `Attachment` model, no product image anywhere (products use an emoji).                                                                                                                                                               | All modern retail/SaaS UIs are image-first.                                           | Polymorphic `Attachment` model + storage + UI upload for products/customers/suppliers/documents.                                               |
| P0       | **Quotations / proforma invoices**                        | No `Quotation`/`QuotationItem` models or UI.                                                                                                                                                                                            | Sales cycle: quote → accept → convert to sale.                                        | Draft→Sent→Accepted→Converted workflow, convertible to `Sale`.                                                                                 |
| P1       | **Automated payment/billing (subscriptions self-renew)**  | Subscriptions exist with manual payment verification; no auto-collection or renewal.                                                                                                                                                    | SaaS needs recurring revenue that renews on time.                                     | Auto-billing via gateway, dunning/retry, renewal events already partially wired in WhatsApp.                                                   |
| P1       | **Loyalty wired into checkout**                           | Loyalty models + `LoyaltyService` exist but earn/burn is **not connected to POS checkout**.                                                                                                                                             | Loyalty that doesn't work at checkout is a hollow feature.                            | Earn points on sale completion, redeem/burn at POS, card lookup in POS.                                                                        |
| P1       | **Barcode scanning at POS**                               | ✅ DONE — exact `GET /pos/products/by-barcode/{barcode}` route (`PosService::scanByBarcode`, tenant/branch-scoped, strips scanner whitespace) + POS Enter scan fast-path with green/red flash + fuzzy-search fallback; `PosBarcodeTest` 5 passing. (`refactor.md` A2) | SKU/barcode scanning is standard in retail.                                           | Add `barcode` field to products + POS scan input (keyboard-wedge/HID already works — just map input).                                          |
| P1       | **Notifications / alerts center polish**                  | ✅ DONE — category filters, unread counts per module (`unread_by_type`), click-through actions, clear-all; `CheckNotificationsJob` fixed (role column bug, per-business scoping, overdue query); manager page wired. (`refactor.md` A1) | Restock, expiry, low-stock, debt alerts are the product's "ops" value.                | Category filters, unread counts across modules, actions from notification.                                                                     |
| P2       | **Finance as single source of truth**                     | `todayswork.md` spec exists; currently CashFlow is the de-facto ledger; no `FinancialTransaction`.                                                                                                                                      | Cleaner cash/bank reporting, reconciliation later.                                    | Build `FinancialTransaction` ledger fed by all workflows; retire CashFlow duplication.                                                         |
| P2       | **Warehouses / stock locations, serial & batch tracking** | Not implemented.                                                                                                                                                                                                                        | Needed for multi-location and FMCG/pharma compliance.                                 | Post-launch.                                                                                                                                   |
| P2       | **Payment reconciliation**                                | Not implemented.                                                                                                                                                                                                                        | Matches gateway statements to ledger.                                                 | Post-launch.                                                                                                                                   |

---

## 6. Overall Advice

1. **Fix the tenant boundary before adding anything else.** A data leak in a multi-tenant SaaS is fatal; every new feature you add now inherits the isolation bug.
2. **Make the WhatsApp/notifications layer real.** The docs (`whatsapp.md`, `sept_refactor.md`) already define exactly what to build (dedupe, preferences, recipients, scheduled jobs, real provider). The foundation is well-designed — finish it and flip off demo mode.
3. **Add tests as you fix.** Coverage is ~85 tests concentrated in 4 areas. Require a test per bug fixed; then add auth + sales/purchases + stock-transfer + loyalty + subscription suites before launch.
4. **Stop building surface area; finish what exists.** There are stubs, dead pages, dead routes, and duplicated components. Delete or wire. A 40% shell of 60 features is weaker than a 100% shell of 25 features.
5. **Consolidate the docs.** The repo root has ~15 planning/refactor markdowns, many overlapping/superseded. Archive them into `docs/decisions/` or delete; keep this roadmap + a short spec.
6. **Productionize early.** HTTPS, error tracking (Sentry), structured logging, DB backups (pg_dump cron), rate limiting, and a CI pipeline (lint + tests + build) — start this behind the hardening work, not after.
7. **Reuse the existing AI/WhatsApp architecture rather than adding tools.** The agent/tool-registry pattern is good; scope all `Tools/*` to the authenticated tenant.
8. **Standardize conventions.** Route-bound unscoped `findOrFail` ↔ branch-scoped indexes, `orders` vs `sale-orders`, `manager` vs `manageradmin` — pick one and refactor.

---

## 7. Delivery Timeline (MVP)

Assumptions: 1 full-stack developer (or an AI-assisted dev), app architecture already substantially built, hardening + gap work only. For a 2–3 person team, multiply output ~1.8–2× and roughly halve the calendar time.

| Phase                                     | Focus                                                                                                                                                                                                            | Effort (dev-weeks) | Deliverable / exit criteria                                                                  |
| ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------ | -------------------------------------------------------------------------------------------- |
| **0 — Harden (weeks 1–2)**                | Tenancy + authz, role-scoping bugs, WhatsApp dedupe migration, queued-job fixes, dead `dd()`s, seeders idempotent, policies invoked.                                                                             | 1.5–2              | No cross-tenant access possible; `php artisan test` green; db:seed idempotent                |
| **1 — Core-gap build (weeks 2–6)**        | Attachments/images, quotations, real WhatsApp/email provider + finished scheduled jobs, mobile-money + card payments at POS, loyalty-at-checkout, notifications polish, real dashboard data, barcode field+scan. | 4–5                | End-to-end demo: quote→sale→POS→mobile-money payment→receipt→WhatsApp receipt; loyalty earns |
| **2 — Test + UX cleanup (weeks 6–8)**     | Add auth/sales/purchase/stock/loyalty/subscription test suites; kill dead UI/pages/logs; strict TS + ESLint; unify manager/admin duplicates.                                                                     | 1.5–2              | Coverage ~40%+ on critical modules; clean `tsc && vite build`                                |
| **3 — Production readiness (weeks 8–11)** | HTTPS/CDN, Sentry, backups, rate limiting, CI, deploy runbooks, seed/onboarding polish, documentation-light.                                                                                                     | 2–3                | Staging + prod deploys; monitoring live; backup restore drill                                |

**Total: ~9–11 dev-weeks (~2.5–3 months) for a 1-dev/AI-assisted team; ~6–7 weeks with a 2–3 person team.**

### Suggested priority order if time-boxed (must-have for MVP)

1. Tenant isolation + role bugs (Phase 0)
2. WhatsApp/email real provider + scheduled alerts (Phase 1)
3. Product images + quotations (Phase 1)
4. Mobile-money payment integration at POS (Phase 1)
5. Idempotent seeders + test suites on critical modules (Phase 2)
6. Production deployment + backups + monitoring (Phase 3)

---

## 8. Definition of Done (MVP)

- [ ] Two separate tenant accounts can run side-by-side with no data leakage (verified by test).
- [ ] Personas (admin / manager / staff / superadmin) work end-to-end including POS.
- [ ] A full sales cycle is demonstrable: quote → sale → payment (mobile money) → stock deduction → cash-flow → digital receipt → WhatsApp receipt.
- [ ] Purchases/returns/transfers reconcile with stock and cash-flow.
- [ ] Tax receipts and tax-payment analytics are correct.
- [ ] Alerts fire via a real provider (not demo).
- [ ] `php artisan test` and `tsc && vite build` pass in CI.
- [ ] Backups + monitoring live on production.
