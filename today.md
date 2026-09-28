# Today's Plan — fixes 1.4, 2.1, 2.3

## 1.4 Damaged/Lost/Expired stock handling

### New tables

- **`product_losses`** — one row per loss event (the "damaged products" register the business wants):
  `id, business_id, business_branch_id, product_id, stock_movement_id (unique), type ENUM('damaged','expired','lost'), quantity, unit_cost (snapshot from product.cost_price), total_loss (qty × unit_cost, cents), reason (free text), loss_date, reported_by, notes, created_at/updated_at`
  - `stock_movement_id` unique → a write-off can never be recorded twice.
- **`losses_summary` is NOT needed** — reports aggregate `product_losses` grouped by branch/date/type.

### Behavior (what happens on a write-off)

1. Single `DB::transaction` + `lockForupdate()` on the product row.
2. Decrement `products.quantity`; guard `qty <= available` (same rule as 1.1).
3. Create `StockMovement` `type='adjustment'`, `reason=damaged|expired|lost`, deterministic `movement_key = md5(product:loss)` for idempotency.
4. Create `ProductLoss` row linked to that movement, `total_loss` in **integer cents**.
5. Create `CashFlow` record `type='expense'`, `category='inventory_loss'`, `total_loss` amount so P&L/drawer math sees the loss (no cash moves — it is a non-cash expense; exclude from cash-drawer inflow/outflow sums by using a dedicated category the drawer query already ignores via its `whereIn` type lists).
6. Log `ActivityLog` entry (via `LogsActivity` on `ProductLoss`).
7. On **expired** write-offs: a scheduled/queued sweep (`app/Console`) marks products past `expiry_date` as `status='expired'` and auto-creates the loss rows so nothing expires silently.

### Code changes

- Migration: `create_product_losses_table`.
- `app/Models/ProductLoss.php` — `LogsActivity`, casts (`quantity` int, `unit_cost`/`total_loss` decimal), relations `product()`, `stockMovement()`, `reportedBy()`.
- `app/Services/InventoryService.php` — `writeOff()` extended to also write `ProductLoss` + `CashFlow` + keep the existing movement; new `recordExpiryLosses()` for the sweep.
- `app/Http/Controllers/ProductLossController.php` — `index` (filter by branch/type/date), `show`, `store` (manual write-off entry point), `summary` (totals per type/period).
- `routes/product-losses.php` + register under `product-losses` prefix in `routes/api.php`.
- Request class `StoreProductLossRequest` (validates `product_id`, `type`, `quantity`, `reason`, `loss_date`).
- `ProductAuditService::approveAudit()` now calls `writeOff()` so audit differences also land in `product_losses`.

### Tests (`tests/Feature/Inventory/ProductLossTest.php`)

- Write-off reduces stock, writes movement + `product_losses` row with correct `total_loss`.
- Cannot write off more than available stock.
- Idempotent: same movement key → no duplicate loss row.
- CashFlow `inventory_loss` record created; drawer variance unaffected.
- Expiry sweep creates losses only for expired in-stock products.

---

## 2.1 Tax calculation and rounding

### Problem

`TaxService` uses float `round()` — risky for money.

### Fix

- Convert `TaxService` to **integer cents** (or `bcmath`) end-to-end: input `unitPrice` as cents (or convert internally with `bcmul`), all math with `bcadd/bcmul/bcdiv`, round-half-up per line, return cents.
- Keep `effectiveRateForProduct()` unchanged.
- Per-line rounding rule: round tax **per sale line** (`qty × discounted unit`), then sum line taxes to `sale.tax_amount` — document this as the canonical rule.
- Same treatment for `PosService` totals (subtotal/tax/grand total summed from rounded line cents) and `SaleItemService`.
- Add a shared `App\Support\Money` helper (cents in/out) so every service uses one implementation.

### Code changes

- `app/Support/Money.php` — `fromFloat/toFloat`, `add/sub/mulDiv/roundHalfUp`.
- `app/Services/TaxService.php` — rewrite `calculateForProduct()` around `Money`; signature accepts `int $unitPriceCents` (update callers in `PosService`, `SaleItemService`).
- `app/Services/PosService.php` + `app/Services/SaleItemService.php` — compute line tax via `Money`, store cents in `sale_items.tax_amount`, `sales.tax_amount` = Σ lines.
- Config default: `config/duukaflow.php` key `tax.rounding = 'line'` (per-line) so the rule is explicit and testable.

### Tests (`tests/Feature/Tax/TaxRoundingTest.php` + extend `TransactionTaxTest`)

- Line tax rounds half-up; e.g. 3 × 333.33 @ 18% → exact cents.
- Multi-line sale tax = sum of rounded line taxes (never recompute on totals).
- Tax-inclusive extraction: `taxable + tax == charged` exactly, for every rate in `[0.05, 0.18, 0.25]`.
- Discount-before-tax still holds (covered by existing test, keep green).

---

## 2.3 Debt and credit balance accuracy

### Current state

- `CustomerCreditService` + `customer_credit_transactions` ledger works for **customer credit**.
- `BusinessDebit` / `BusinessCredit` exist as tables but controllers are empty stubs — **supplier debits and business credits are unmanaged**.
- No lifecycle (issued → paid → partially paid → overdue → settled), no overdue tracking.

### Fix

Unify on one ledger pattern (like `customer_credit_transactions`) for both directions:

**A. BusinessDebit (money we owe suppliers)**
- Fill `BusinessDebitController`: `index/show/store/update/destroy`.
- `store`: create debit (`status='open'`) + optionally a `business_debit_payments` row.
- Migration: `business_debit_payments` (`id, business_debit_id, amount, payment_date, reference, notes, created_by`) — payment ledger.
- `BusinessDebit` gains: `due_date`, `amount_paid` (cached), `balance` accessor computed from payments, status derived: `settled` when balance = 0 else `open`; `overdue` derived from `due_date < today && balance > 0` (scope `scopeOverdue`).
- Editing the debit recalculates from ledger — no cached-balance drift.

**B. BusinessCredit (money customers/others owe the business)**
- Same treatment: `BusinessCreditController` + reuse `customer_credit_transactions` already used for POS credit sales. Add `due_date`, overdue scope, and `credit_lifecycle_status()` accessor (`issued/paid/partial/overdue/settled`).

**C. Shared**
- `app/Services/DebtService.php` — `recordPayment(debt, amount)`, `recalculate(debt)`, `overdueIds(branchId)`; asserts payment ≤ outstanding balance and branch scope via `EffectiveBranchScope`.
- Both controllers use `LogsActivity` on their models (audit trail per fixes 3.2).
- Routes: fill `routes/finances.php` entries (`business-debits/...`, `business-credits/...`).

### Tests (`tests/Feature/Finance/BusinessDebitTest.php`, `BusinessCreditTest.php`)

- Debit created with `status=open`, balance = amount.
- Partial payment reduces balance; overpayment rejected.
- Payment that settles → `status=settled`, `amount_paid` = original.
- Past-due unsettled debit appears in `scopeOverdue`.
- Refund/return on a credit sale adjusts balance via existing `recordRefund` (already covered).
- Branch isolation: user cannot post payments to another branch's debt.

---

## Execution order (single sitting)

1. `Money` helper + `TaxService` cents rewrite + tax tests (2.1) — foundational.
2. `product_losses` migration + `ProductLoss` model/service/controller + expiry sweep + tests (1.4).
3. `business_debit_payments` migration + `DebtService` + debit/credit controllers + tests (2.3).
4. Run `php artisan test` (or `composer test`) — all green before stopping.
