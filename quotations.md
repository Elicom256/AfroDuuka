# DuukaFlow — C1: Quotations / proforma invoices (roadmap P0)

> The lightest of the remaining heavy features (`industry-features.md` #1, `refactor.md` §4 C1).
> Scope aligned to `proposal.md` (The Order Workflow): **Quote → Sales Order (allocated/reserved) →
> Shipment → Invoice.** C1 ships the first two steps end-to-end; shipment + invoice are explicitly
> stubbed as downstream follow-ups so financials are never touched prematurely.

## The order workflow (source of truth: proposal.md)

1. **Quote** — wholesaler creates a price offer. Inventory not affected, no sale recorded. *(C1 ships this.)*
2. **Sales Order (SO)** — client accepts the quote. System creates a Sales Order. Inventory is now
   **Allocated/Reserved** so no one else can buy it, but it's still physically in the warehouse. No
   financial sale recorded. *(C1 ships this, incl. allocation quantities; POS enforcement is follow-up.)*
3. **Shipment/Delivery** — pick/pack/ship. **Physical inventory drops.** *(Follow-up: `StockMovement` 'out'.)
   Derby: `sales_order_items.shipped_qty` incremented; `Product.quantity` decremented by shipped_qty.*
4. **Invoice** — bill the client. **This is the exact moment the sale is officially recorded** — revenue
   recognized, accounts receivable up. *(Follow-up: creates `Sale` via same `TaxService` pricing path →
   totals stay consistent with the quote/order.)*

### What this guard against
- **Ghost revenue in P&L**: a `Sale` is only ever created at Invoice, so rejected quotes can never show
  as revenue or distort taxes.
- **Inventory drift**: stock never leaves the warehouse at Quote or SO time, so you never turn away real
  buyers because of a deal that didn't happen.

## Current state (verified in code)

- **No quotation code anywhere** (grep confirms no `quotation`/`proforma` in `app/`).
- **`SaleOrder` already exists** (`sale_orders` / `sale_order_items`) — the Sales Order target for step 2.
  It was recently made explicit: table renamed `orders`→`sale_orders`, FK `order_id`→`sale_order_id`,
  joined by `purchase_orders` (which was already well named), and both share a lifecycle enum
  `pending | approved | cancelled`. Controllers/FormRequests were refactored accordingly (`StoreSaleOrderRequest`,
  `UpdateSaleOrderRequest`, `StorePurchaseOrderRequest`, `UpdatePurchaseOrderRequest`), and live-DB reconciliation
  handled in `2026_09_24_180000_refactor_orders_to_sale_orders`. Routes are now explicit: `/api/sale-orders`,
  `/api/purchase-orders`.
- `Sales`/`SaleItem` already model exactly what a line-level financial record needs (`subtotal`, `tax_amount`,
  `total_amount`, per-item `discount → taxable_amount → tax_amount → subtotal`). **Line math mirrors this shape**
  so the Invoice follow-up (step 4) maps 1:1.
- **Pricing engine exists:** `TaxService::calculateForProduct($product, $unitPrice, $qty, $discount)` →
  `rate, is_tax_inclusive, discounted_amount, taxable_amount, tax_amount`. Reuse it verbatim at every step so
  quote ↔ SO ↔ invoice totals always agree.
- **PDF pipeline exists:** `barryvdh/laravel-dompdf` + `ReceiptController::pdf()` + `views/pdfs/receipt.blade.php`
  (incl. `Accept: json` → base64 branch). Mirror for the quotation PDF.
- **Branch scoping is automatic:** models extend `BaseModel` → `business_id` auto-set + `EffectiveBranchScope`
  global scope; route-model binding inherits it. Products are branch-scoped via `Product`.
- **Number generation pattern:** `PosService::generateReceiptNumber()` = `POS-YYYYMMDD-####` daily counter.
  Use `QT-...` for quotations per business.

## Design decisions

- **Quote carries an item snapshot** (`product_name`, `sku`) per line so the PDF/record survives product
  renames/deletes during the validity window. Product FK kept for the editor.
- **Accepting a quote creates a `SaleOrder` — never a `Sale`.** `SaleOrder` gets `quotation_id`; its items get
  `allocated_qty`/`shipped_qty` (allocation counters = the "no one else can buy it" record). No `Sale`, `Receipt`,
  `CashFlow` or `SalePayment` anywhere until the Invoice follow-up.
- **Allocation = reserved quantities.** `sale_order_items.allocated_qty` filled at order creation
  (from a quote accept **or** a direct SO); shipped stays 0. `Product::availableQuantity()` (= on-hand − Σ active
  allocations) is used for SO/quote line validation; wiring it into POS `validateCart` is a documented follow-up.
- **Cancellation releases allocation** (`SaleOrder` status `cancelled` ⇒ its allocations no longer count in
  `availableQuantity`).
- **Idempotent acceptance:** an accepted/expired/cancelled quote cannot be accepted again → 409.
- **Expiry:** `valid_until` date; list query marks overdue draft/sent quotes as logically `expired`.
- **New `QuotationPolicy`** mirroring `ProductPolicy` (branch-set check via `EffectiveBranchScope`) and invoked —
  policy-on-build discipline from the L1 sweep.
- Unit price stays editable per line (enterprises negotiate); discounts per line supported via
  `TaxService::calculateForProduct`.

## Backend

### 1. Migrations
- `create_quotations_table`:
  `id, business_id(index), business_branch_id, user_id(nullable), customer_id(nullable),
   quotation_number(index), status enum[draft, sent, accepted, expired, cancelled] default draft,
   valid_until(date, nullable), currency default UGX, subtotal/tax_amount/discount/total_amount decimal(12,2),
   notes(text nullable), terms(text nullable), accepted_order_id(nullable FK sale_orders -> nullOnDelete),
   timestamps`.
- `create_quotation_items_table`:
  `id, quotation_id(FK cascade), product_id(FK cascade), product_name, sku(nullable), quantity(int),
   unit_price, discount decimal(12,2) default 0, tax_rate decimal(5,4) nullable,
   is_tax_inclusive bool default false, taxable_amount default 0, tax_amount default 0,
   subtotal decimal(12,2), timestamps` + index on `quotation_id`.
- `add_allocations_to_sale_orders`:
  `sale_orders.quotation_id` (nullable FK sale_orders -> nullOnDelete);
  `sale_order_items.allocated_qty` int default 0 + `shipped_qty` int default 0.

### 2. Models
- `Quotation extends BaseModel`: fillable + relations `customer()`, `user()`, `items()` (HasMany),
  `acceptedOrder()` (BelongsTo SaleOrder).
- `QuotationItem` (plain Model, parent-scoped): `quotation()`, `product()`.
- `SaleOrder` (existing): add `quotation_id` fillable + `quotation()` BelongsTo. Items set
  `allocated_qty = quantity` on creation.
- `SaleOrderItem` (existing): add `allocated_qty`/`shipped_qty` fillable + casts.
- `Product`: add `availableQuantity()` helper — on-hand − Σ allocated_qty of non-cancelled SOs.
- `Sale` untouched (Invoice follow-up creates it).

### 3. Service — `QuotationService`
- `create(array, ?int $branchId)`: validate each item against `Product::availableQuantity()` in-branch (mirror
  `PosService::validateCart`), compute line tax via `TaxService::calculateForProduct`, quote number `QT-YYYYMMDD-####`,
  create `Quotation` + `QuotationItem`s.
- `update(Quotation, array)`: replace items + recompute only while status is `draft|sent` (locked once accepted).
- `send` (draft→sent), `accept(Quotation, ?int $branchId)`: idempotency guard (not draft/sent ⇒ throw); in a single
  `DB::transaction`: create `SaleOrder` (status `approved`, `quotation_id` set) + `SaleOrderItems`
  (`allocated_qty = quantity`), set `quotation.status = accepted` + `accepted_order_id`. **No stock change,
  no financial rows.**
- `cancel(Quotation)`: draft/sent only → `cancelled` (releases nothing yet since nothing allocated).
- `expiredQuery()`: valid_until < today, status not accepted/cancelled.
- `SaleOrderService` (thin): `cancel(order)` → `cancelled` (allocations stop counting). Shipment/invoice
  methods are **not implemented** — flagged in code as follow-ups per proposal.md steps 3–4.

### 4. Routes (`api/routes/quotations.php`, registered in `routes/api.php`)
```
GET    /quotations                index (filter: status, customer_id, user_id, date_from/to, search quotation_number) paginated
POST   /quotations                store
GET    /quotations/{quotation}    show
PATCH  /quotations/{quotation}    update (draft/sent only)
DELETE /quotations/{quotation}    destroy (draft/sent only)
POST   /quotations/{quotation}/send
POST   /quotations/{quotation}/accept     → creates SalesOrder
POST   /quotations/{quotation}/cancel
GET    /quotations/{quotation}/pdf
```
All behind `auth:sanctum`. Explicit sub-routes (`send/accept/cancel/pdf`) registered **before** the
`{quotation}` wildcard (mirror `products.php` ordering).

### 5. Controller + Requests
- `QuotationController`: thin wrappers over `QuotationService`; `authorize()` via `QuotationPolicy`
  (`view/update/delete`); `accept` returns the created `SaleOrder` (loaded with items); not-acceptable → 409;
  `pdf` mirrors `ReceiptController::pdf` (base64 on `Accept: json`).
- `StoreQuotationRequest` / `UpdateQuotationRequest`: `customer_id` nullable int; `items` required array min 1
  (each: `product_id` int exists, `quantity` min 1, `unit_price` numeric ≥ 0, `discount` numeric ≥ 0);
  `valid_until` date nullable; `notes`/`terms` strings; `currency` default UGX.

### 6. PDF view
- `resources/views/pdfs/quotation.blade.php` modeled on `receipt.blade.php`: business letterhead, customer
  block, quote number + date + valid_until, line table (name/sku/qty/unit/discount/tax/line total),
  subtotal/tax/total, terms + status watermark. (SO PDF can reuse it later with the order number header.)

## Tests — `api/tests/Feature/QuotationTest.php` (`RefreshDatabase`)

1. `create_quotation_computes_totals_like_pos` — totals match a `PosService::checkout` on the same cart.
2. `quotation_number_format` — `QT-\d{8}-\d{4}`.
3. `list_filters_by_status_and_customer`.
4. `update_replaces_items_and_recomputes` (draft).
5. `update_locked_after_accept` → 422/403.
6. `send_transition` (draft→sent).
7. `accept_creates_sale_order_without_touching_stock_or_financials` — `SaleOrder` + items created
   (`allocated_qty=qty`), `product.quantity` unchanged, **no** Sale/Receipt/CashFlow/SalePayment rows,
   quote → `accepted` + `accepted_order_id` set.
8. `accept_idempotent` — second accept → 409, exactly one SO.
9. `availableQuantity_excludes_cancelled_orders` — active SO reduces available; cancelled SO restores it.
10. `create_rejects_allocation_overrun` — item qty > available → 422.
11. `branch_scoped_user_cannot_access_other_branch_quote` → 404.
12. `expired_query_is_excluded_from_active_list`.
13. `pdf_returns_json_base64_and_stream` — 200 both ways.
14. `destroy_only_for_draft_or_sent`.

## Frontend (`ui/src/app/...`)

1. `store/features/business/quotations/quotationsQuery.ts` — RTK api (`baseUrl: VITE_BASE_URL`,
   `tags: ['QuotationsAPI']`): `quotations` (query), `quotation` (query), `addQuotation`, `updateQuotation`,
   `sendQuotation`, `cancelQuotation`, `acceptQuotation` (returns the SO), `deleteQuotation`,
   `downloadQuotationPdf` (blob). Follows `priceHistoryQuery.ts`/`posQuery.ts` conventions.
2. **Editor dialog** (`components/quotations/QuotationEditor.tsx`) mirroring the POS cart: product search
   (reuse branch products query), line table with editable `unit_price`/`discount`/`qty`, live totals via
   mirrored math, customer select (existing customers query), `valid_until`, notes/terms.
3. **List page** (`pages/.../quotations/QuotationsPage.tsx`): table + status chips, filters, per-status actions
   (Send / Accept → Sales Order / Cancel / PDF), delete for drafts. Register in admin + manager route trees
   (`AdminRoutes.tsx`/`ManagerRoutes.tsx`).
4. **Detail**: items, totals, status; when accepted, show linked Sales Order number + its allocation summary.

## QA (manual)

1. Create a quote for items that a POS cart would show — verify totals identical.
2. Send → Accept: confirm a Sales Order appears, **stock unchanged**, quote shows SO link.
3. Attempt to accept again → error, no duplicate SO.
4. Create a second quote exceeding `availableQuantity` of a reserved item → rejected.
5. Download PDF renders letterhead/lines/totals + status.
6. Cross-branch user → 404.

## Execution order

1. Migrations + `Quotation`/`QuotationItem` models; extend `SaleOrder`/`SaleOrderItem` with allocation columns.
2. `Product::availableQuantity()` + `QuotationService` (create/update/send/accept/cancel/expired).
3. `SalesOrderService::cancel` + comment stubs for shipment/invoice (proposal.md steps 3–4).
4. `QuotationPolicy`, requests, `QuotationController`, routes in `api.php` (`route:clear` after).
5. PDF view + endpoint.
6. `QuotationTest`; run suite, fix; `php -l`.
7. Frontend query → editor → list page → route registration → PDF download wiring.
8. `npx tsc -b`, eslint (no new violations), full `php artisan test` green.
9. Docs: `refactor.md` §4 C1 → DONE (include SO/steps note), `industry-features.md` row 1 → done (note
   proposal.md alignment), `roadmap.md` §5 row marked done. Add follow-ups to
   `industry-features.md` notes: POS `validateCart` respects allocations; Shipment (step 3) + Invoice (step 4).
   Final: `docker exec duukaflow-backend-1 php artisan route:clear` after route additions.