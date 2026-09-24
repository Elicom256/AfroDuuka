# DuukaFlow — Implementation Plan: Core 2026 Features (roadmap §5)

> Plan for the features listed in `roadmap.md` → **§5. Core 2026 Features That Are NOT Implemented (yet)**.
> Ordered by **effort-to-implement, lowest first** so we bank quick wins before the heavy builds.
> Update as work lands. Each item links to the roadmap row it implements.

---

## Ordering principle

1. **Low energy / low API surface first** — polish + wiring of already-built machinery.
2. **Medium builds** — one new model + one new screen.
3. **Heavy builds** — new models, gateways/providers, background jobs, webhooks.
4. **Post-launch** — explicitly out of MVP scope in roadmap §2.

| Wave | Feature | Effort | Roadmap row |
|---|---|---|---|
| **A** | Notifications / alerts center polish | Low | P1 |
| **A** | Barcode scanning at POS | Low | P1 |
| **B** | Loyalty wired into checkout | Medium | P1 |
| **B** | Product images & document attachments | Medium | P0 |
| **C** | Quotations / proforma invoices | High | P0 |
| **C** | Automated payment/billing (subscriptions self-renew) | High | P1 |
| **C** | Customer communication (email/SMS/WhatsApp) | High | P0 |
| **D** | Finance as single source of truth | High | P2 |
| **D** | Real payment collection (mobile money / card) | High | P0 |
| **D** | Warehouses / stock locations, serial & batch | High | P2 |
| **D** | Payment reconciliation | High | P2 |

> **Dependency note:** Wave D payments/finance should follow Quotations (C) so the ledger and
> payment gateway can be fed by the full sales cycle (quote → sale → payment) at once.

---

## WAVE A — Quick wins (low energy, minimal new API)

### A1. Notifications / alerts center polish  — roadmap P1

**Goal:** turn in-app notifications from "list + polling" into an ops center: filter by category,
unread counts per module, and actions that jump the user to the relevant record.

**Current state:**
- Notification model exists with types; a controller fetches/list marks-reads; frontend polls a
  notifications endpoint and shows a dropdown/center.
- Generators already exist as queued jobs (low-stock, expiring, dues) — some are dead code and were
  fixed in roadmap §3.4 (`CheckNotificationsJob`); not all are wired to actually **create** in-app rows.

**Backend changes (small):**
- Add `type`/`category` filtering + `paginate` to the list endpoint (query params only, no new model).
- Add unread-count endpoint returning per-category counts (`unread_by_category`) — one lightweight
  grouped query.
- Ensure every alert job that pushes in-app notifications is actually scheduled (route the
  low-stock/expiry/restock generators that are currently unwired).
- Add `notification_action_url`/`meta` payload on creation so UI can jump to the record.

**Frontend changes:**
- Notifications screen: category filter chips (low-stock, expiry, dues, system), unread badge per chip.
- Mark-as-read (`POST read/{id}` — extend existing) and mark-all-read.
- Click-through actions from each notification to its record page.

**Tests:** controller tests for filter + unread counts; job test that generators create rows.
**API surface:** ~0 new models, 1–2 enriched endpoints.

**Status: DONE (backend + frontend + tests).**
- `NotificationController`: `index` accepts `?type=|type=a,b` + `?is_read=`, meta now returns `unread` and
  `unread_by_type`; `unreadCount` returns both `unread_count` and grouped `unread_by_type`. New
  `POST /users/notifications/clear-all` (soft-deletes only the caller's rows). Route added in `routes/users.php`.
- `CheckNotificationsJob` rewritten: `getAdminUser` bug (`where('role','admin')` — `role` is a relation,
  not a column) removed; now scans per business and notifies that business's admin(s) (no cross-tenant
  leak); overdue-payment query no longer references the non-existent `sales.paymentStatus`
  (renamed to `status`); low-stock dedupe now keyed per user+business+type within 24h; overdue alerts get
  the same dedupe + notifiable link. Scheduled every 6h already in `routes/console.php`.
- `NotificationService` alert methods now carry `notifiable_type/id` (`Product`, `Sale`, `Purchase`) with
  ids in the `data` payload (e.g. `product_id`, `sale_id`, `purchase_id`) — callers
  (`SaleItemService`, `PosService`, `PurchaseService`) pass the record ids. `Customer::name()` helper added.
- Frontend: `notificationsQuery` gains `getUnreadCount` + `clearAll` and `getNotifications` filter params;
  admin notifications page has filter chips with per-type unread badges, wired Clear All, and
  click-through navigation (shared `notificationUtils.ts` maps type→route per role); `NotificationItem`
  shows type label + "View" click-through; `ManagerNotificationsPage` now fetches real data (was a stub);
  `AdminSidebar` unread badge polls the lightweight `unread-count` endpoint (60s) instead of the full list.
- Tests: `api/tests/Feature/NotificationTest.php` (8 tests: tenant isolation, type/is_read filter,
  `unread_by_type` breakdown, unread-count grouping, mark-read ownership, clear-all scoping).

---

### A2. Barcode scanning at POS  — roadmap P1  ✅ DONE

**Goal:** scan a product barcode (keyboard-wedge/HID USB scanner "types" the code) → row adds to cart.

**Current state:**
- `products.barcode` + `sku` columns already exist.
- POS search endpoint matches name/SKU/barcode already and the POS search bar exists.
- No dedicated scan input mapping the scanner into add-to-cart.

**Status: DONE — Full implementation (backend + frontend + tests).**
- Backend: new `GET /pos/products/by-barcode/{barcode}` route (`api/routes/pos.php`);
  `PosService::scanByBarcode()` strips scanner whitespace/newline, does an exact tenant/branch-scoped
  barcode lookup (returns `PosProductResource` or empty), and `PosController::byBarcode()` returns
  404 `Product not found` / 422 when empty.
- Frontend: `posQuery.ts` gains `searchProductByBarcode` (+ `useLazySearchProductByBarcodeQuery`).
  `PosPage.handleBarcodeSubmit` now does the exact scan lookup on Enter → adds to cart + clears +
  refocuses; on miss fallbacks to the existing fuzzy search (exactly-1 match adds it), otherwise
  "No product found" toast. Green ring flash on scan hit, red ring on miss (400ms).
- Tests: `api/tests/Feature/POS/PosBarcodeTest.php` — exact resolve + resource shape, unknown → 404,
  scanner newline/whitespace stripping, branch-isolated product → 404, blank → 422. **5/5 passing.**
- Full suite: **108 passed / 2 failed** (the 2 pre-existing POS auth tests expecting 302).

**API surface:** 1 small route.

---

## WAVE B — Medium builds (one new model / screen each)

### B1. Loyalty wired into checkout  — roadmap P1

**Goal:** loyalty earns points on completed sale; POS can look up a customer's card and redeem/burn points at checkout.

**Current state:** loyalty models + `LoyaltyService` exist with earn/burn methods, **but nothing calls
them from POS checkout / sale completion.**

**Plan:**
1. Hook earn into sale completion: on successful sale, call `LoyaltyService` earn against the
   sale customer (idempotent — keyed by sale, so re-runs/refunds don't double-earn).
2. POS redemption: card lookup field in the POS customer panel → shows balance → buyer selects redeem →
   discount applied in the POS totals → burn on payment success.
3. Backend: ensure `LoyaltyService` methods used are tenant/branch-scoped; add any missing
   balance/redeem endpoints behind existing auth.
4. Refund handling: reverse earned points on sale refund (with refund event).

**Tests:** unit tests on earn/burn idempotency; feature test sale→earn, redeem→sale totals.
**API surface:** reuse `LoyaltyService`, wire 1–2 POS endpoints.

---

### B2. Product images & document attachments  — roadmap P0  ✅ DONE

**Goal:** polymorphic attachments (product images, customer/supplier docs) with storage + UI upload.

**Status: DONE — full implementation (backend + frontend + tests).**
- New polymorphic `Attachment` model + `2026_09_24_174333_create_attachments_table` migration
  (`attachable_type/id`, `disk`, `path`, `original_name`, `mime_type`, `size`, `kind`=image|document,
  business/branch-scoped via `BaseModel`; index on `attachable_type+attachable_id`). `url` accessor via `Storage::url`.
- Storage on the `public` disk (`storage/app/public`, `storage:link` created). PHP has **no GD/Imagick** in the
  container, so no server-side thumbnail resize — originals are served full-size (browser scales in cards).
- Routes: `POST|GET /products/{product}/attachments`, `DELETE /products/{product}/attachments/{attachment}`
  (products, authorize via real `ProductPolicy`), plus customer/supplier doc equivalents under
  `/admin/customers|suppliers/{id}/attachments` (follow existing no-policy controller pattern; relied on
  branch-scoped route binding). `Product::attachments()` morphMany + `cover_url` accessor (in `$appends`,
  first image = cover); `ProductController` index/show eager-load `attachments`.
- Frontend: `attachmentsQuery.ts` (get/upload/delete product images); shared `ProductImageManager` component
  (cover display, upload, gallery, delete) embedded in admin + manager product detail pages; cover thumbnails
  in admin + manager `ProductTable` (emoji fallback); image picker in admin `AddProduct` (uploads after create).
  Customer/supplier doc UI slots left for later (backend ready).
- Tests: `tests/Feature/AttachmentTest.php` — upload + file exists + tenant fields, document kind, list, delete,
  cross-branch 404 (branch-scoped binding), missing file 422, disallowed mime 422 + no row, customer docs,
  `cover_url`+`attachments` in product show. **9/9 passing.**
- Full suite: **117 passed / 2 failed** (the 2 pre-existing POS auth tests expecting 302).

**API surface:** 1 new model + 9 routes (3 per parent).

---

## WAVE C — Heavy builds

### C1. Quotations / proforma invoices  — roadmap P0

**Goal:** Draft → Sent → Accepted → Converted workflow; converting an accepted quote creates a `Sale`.

**Plan:**
1. New `Quotation` + `QuotationItem` models (business/branch-scoped, requote number, item snapshot of
   price/qty, validity, status enum, notes, discount/tax like sales).
2. Routes (CRUD + `accept` + `convert`), tenant-scoped, `convert` builds a `Sale` via the same
   service used by POS checkout (so stock/cash-flow/ledger stay consistent).
3. Quote number generator + PDF/download (reuse the existing receipt PDF pipeline).
4. UI: Quotes list (status chips), Quote editor (mirrors POS cart), Quote detail with
   Send (email/WhatsApp later) and Convert to Sale.
5. Conversion idempotency (quote marked converted; cannot double-convert).

**Tests:** model, workflow feature tests (convert→sale stock/cash-flow), idempotency.
**Note:** this slots in before Wave D so Payments and Finance can consume converted sales.

---

### C2. Automated payment/billing (subscriptions self-renew)  — roadmap P1

**Goal:** subscriptions renew automatically via gateway with dunning/retry.

**Plan:**
1. Extend `Subscription` with auto-renew flag, next_billing_date, grace period; add `SubscriptionPayment`
   statuses (pending/paid/failed/succeeded-retry).
2. Scheduled job on `next_billing_date`: attempt charge via gateway (Wave D), record payment,
   extend period, notify via WhatsApp/email (Wave C3). On failure → retry schedule + dunning notice.
3. Webhook handler for gateway charge results (idempotent).
4. UI: subscription page shows auto-renew toggle, billing history, retry button on failed.
5. Depends on C3 (provider) + D1 (gateway) for real charging; structure so the job can run in
   "manual verify" mode until the gateway lands.

---

### C3. Customer communication (email/SMS/WhatsApp)  — roadmap P0

**Goal:** real providers behind the existing `WhatsAppService` abstraction; add email; finish scheduled jobs.

**Plan:**
1. `WHATSAPP_PROVIDER=demo` → provider adapters (WhatsApp Business API + email transport) behind one
   `NotificationChannel` interface; demo stays as fallback.
2. Add email template pipeline + unsubscribe/preferences.
3. Wire scheduled jobs (expiry, monthly report, low-stock, receipt on sale, quote send) to real providers.
4. Dedupe/preferences already specced in `whatsapp*.md` — implement the dedupe column fix from roadmap §3.3
   and preferences filtering.
5. Flop `WHATSAPP_PROVIDER` to a real provider last after test coverage.

---

## WAVE D — Post-launch / heavy (roadmap P2 + P0 payments)

### D1. Finance as single source of truth  — roadmap P2

**Goal:** `FinancialTransaction` ledger fed by all workflows; retire CashFlow duplication.

**Plan:** new ledger model + writers wired into sales/purchases/expenses/transfers/refunds; report
queries migrate to it; keep CashFlow as read-only facade temporarily; cutover + drop duplicates.
(Split into sub-tasks; spec exists in `todayswork.md`.)

### D2. Real payment collection (mobile money / card)  — roadmap P0

**Goal:** POS takes MTN MoMo / Airtel Money / card via gateway.

**Plan:** gateway adapter (1 mobile-money provider + card), POS split-payment connects to gateway
(already exists client-side), webhook verification + reconciliation hooks with D1. Pairs with C2.

### D3. Warehouses / stock locations, serial & batch tracking  — roadmap P2

Straightforward but large schema/scope change (post-MVP, excluded from roadmap §2 MVP).

### D4. Payment reconciliation  — roadmap P2

Matches gateway statements to ledger entries; layered on D1 + D2.

---

## Suggested execution order (time-boxed)

1. **A1 Notifications polish** + **A2 Barcode scanning** (quick wins)
2. **B1 Loyalty at checkout** (high demo value, machinery exists)
3. **B2 Attachments/images** (unblocks UI visual quality)
4. **C1 Quotations** → then **C2 auto-billing** + **C3 comms** as provider layers
5. **D1 Finance ledger**, then **D2 Payments** on top of it, finally **D3/D4**

Each feature ships with: backend tests (per roadmap §6 advice), tenant-scoping checked, and the
roadmap §5 row marked done.