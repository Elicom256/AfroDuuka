# Done — DuukaFlow Refactor

## Bug Fixes

### 1. `BusinessBranch::business()` — Missing `return`
- **File:** `api/app/Models/BusinessBranch.php:15`
- **Fix:** Added `return` before `$this->belongsTo(Business::class)`

### 2. Broken `auth.php` route file
- **Files deleted:** `api/routes/auth.php`
- **Reason:** All route methods referenced `PurchaseController` (login, logout, me, CRUD) which don't exist there. Real auth is in `users.php`.
- **Updated:** `api/routes/api.php` — removed the `Route::prefix("auth")` block

### 3. Broken `orders.php` route file
- **Files deleted:** `api/routes/orders.php`
- **Reason:** Same copy-paste issue as `auth.php` — referenced `PurchaseController` for order routes
- **Updated:** `api/routes/api.php` — removed the `Route::prefix("orders")` block

### 4. Security: `console.log(token)` in production
- **File:** `ui/src/app/store/features/auth/authQuery.ts:11`
- **Fix:** Replaced `console.log('Available token==>', token)` with a silent comment

### 5. Missing `/api/finances` endpoint (causing 404)
- **File created:** `api/routes/finances.php`
- **Route:** `GET /api/finances/` (auth:sanctum protected) → `CashFlowController@index`
- **Updated:** `api/routes/api.php` — added prefix "finances" include

### 6. `StockMovement` — ambiguous `product_id` FK
- **Migration:** `2026_01_01_000013_create_stock_movements_table.php`
- **Changes:**
  - Changed `product_id` → `business_branch_product_id` (references `business_branch_products`)
  - Added `business_branch_id` FK
- **Model:** `api/app/Models/StockMovement.php`
  - Updated `$fillable`, added `businessBranchProduct()` relationship, changed `product()` to a shortcut via the pivot

---

## Full System Wiring (Model → Form Request → Controller → Service → Route)

Each new feature has a full backend stack. Here's the complete map:

### Currency on BusinessBranch
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001215_add_currency_to_business_branches.php` |
| Model | `BusinessBranch` (currency in `$fillable`) |

### Currency Rates (`/api/currency-rates`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001216_create_currency_rates_table.php` |
| Model | `app/Models/CurrencyRate.php` |
| Store Request | `app/Http/Requests/StoreCurrencyRateRequest.php` |
| Update Request | `app/Http/Requests/UpdateCurrencyRateRequest.php` |
| Controller | `app/Http/Controllers/CurrencyRateController.php` |
| Route | `routes/currency-rates.php` → `api.php` prefix `currency-rates` |

### Payment Gateways (`/api/payment-gateways`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001217_create_payment_gateways_table.php` |
| Model | `app/Models/PaymentGateway.php` |
| Store Request | `app/Http/Requests/StorePaymentGatewayRequest.php` |
| Update Request | `app/Http/Requests/UpdatePaymentGatewayRequest.php` |
| Controller | `app/Http/Controllers/PaymentGatewayController.php` |
| Route | `routes/payment-gateways.php` → `api.php` prefix `payment-gateways` |

### Printers (`/api/printers`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001218_create_printers_table.php` |
| Model | `app/Models/Printer.php` |
| Store Request | `app/Http/Requests/StorePrinterRequest.php` |
| Update Request | `app/Http/Requests/UpdatePrinterRequest.php` |
| Controller | `app/Http/Controllers/PrinterController.php` |
| Route | `routes/printers.php` → `api.php` prefix `printers` |

### Stock Transfers (`/api/stock-transfers`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001219_create_stock_transfers_table.php` + `001220_items` |
| Model | `app/Models/StockTransfer.php`, `StockTransferItem.php` |
| Store Request | `app/Http/Requests/StoreStockTransferRequest.php` (includes items validation) |
| Update Request | `app/Http/Requests/UpdateStockTransferRequest.php` |
| Service | `app/Services/StockTransferService.php` — handles dispatch (decrement source), receive (increment dest), cancel |
| Controller | `app/Http/Controllers/StockTransferController.php` |
| Custom endpoints | `POST /{id}/dispatch`, `POST /{id}/receive`, `POST /{id}/cancel` |
| Route | `routes/stock-transfers.php` → `api.php` prefix `stock-transfers` |

### Reorder Rules (`/api/reorder-rules`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001221_create_reorder_rules_table.php` |
| Model | `app/Models/ReorderRule.php` |
| Store Request | `app/Http/Requests/StoreReorderRuleRequest.php` |
| Update Request | `app/Http/Requests/UpdateReorderRuleRequest.php` |
| Controller | `app/Http/Controllers/ReorderRuleController.php` |
| Route | `routes/reorder-rules.php` → `api.php` prefix `reorder-rules` |

### Tax Invoices (`/api/tax-invoices`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001222_create_tax_invoices_table.php` |
| Model | `app/Models/TaxInvoice.php` |
| Store Request | `app/Http/Requests/StoreTaxInvoiceRequest.php` |
| Update Request | `app/Http/Requests/UpdateTaxInvoiceRequest.php` |
| Controller | `app/Http/Controllers/TaxInvoiceController.php` |
| Custom endpoint | `POST /{id}/submit-to-ura` |
| Route | `routes/tax-invoices.php` → `api.php` prefix `tax-invoices` |

### Loyalty (`/api/loyalty/*`)
| Layer | File |
|-------|------|
| Migrations | `001223_programs`, `001224_cards`, `001225_transactions`, `001226_rewards` |
| Models | `LoyaltyProgram`, `LoyaltyCard`, `LoyaltyTransaction`, `LoyaltyReward` |
| Store/Update Requests | 9 Form Request files (Store + Update for Program, Card, Reward; Store for Transaction) |
| Service | `app/Services/LoyaltyService.php` — earnPoints, burnPoints, adjustPoints |
| Controllers | `LoyaltyProgramController`, `LoyaltyCardController` (with earn/burn/adjust), `LoyaltyRewardController` |
| Route | `routes/loyalty.php` → `api.php` prefix `loyalty` with nested resources |

### Report Exports (`/api/report-exports`)
| Layer | File |
|-------|------|
| Migration | `2026_06_16_001227_create_report_exports_table.php` |
| Model | `app/Models/ReportExport.php` |
| Store Request | `app/Http/Requests/StoreReportExportRequest.php` |
| Update Request | `app/Http/Requests/UpdateReportExportRequest.php` |
| Controller | `app/Http/Controllers/ReportExportController.php` |
| Route | `routes/report-exports.php` → `api.php` prefix `report-exports` |

---

## Frontend Changes

### Centralised Types
- **Created:** `ui/src/types/index.ts` — shared `ApiResponse<T>`, `ApiError`, `Currency`, `ToggleSetting`
- **Extended:** `ui/src/types/periodFilter.ts` — added `PERIODS` constant, `formatPeriodLabel()`, `getDefaultPeriod()`
- **Updated:** `ui/src/app/pages/dashboards/admin/components/periodHelper.ts` — re-exports from `@/types`

### Reusable `PeriodFilterBar` Component
- **Created:** `ui/src/app/pages/dashboards/admin/components/PeriodFilterBar.tsx`
- All analytics components can now import this instead of duplicating period buttons

### Settings Page Redesign
- **File:** `ui/src/app/pages/dashboards/admin/pages/settings.tsx`
- **Changes:**
  - Search/filter on navigation items
  - Card-based grid overview with quick links
  - Theme toggle (Light/Dark/System) using `next-themes`
  - Active state indication with `ChevronRight`
  - Better visual hierarchy with icons and descriptions

### Settings Components Cleaned
- Removed `console.log('settings==>', data)` from `PaymentSettings.tsx`
- Removed `console.log('Available token==>', token)` from `authQuery.ts`

---

## Files Deleted
| File | Reason |
|------|--------|
| `api/routes/auth.php` | Broken dup of user routes |
| `api/routes/orders.php` | Broken copy-paste of auth routes |

### Migrations (13)
- `2026_06_16_001215_add_currency_to_business_branches.php`
- `2026_06_16_001216_create_currency_rates_table.php`
- `2026_06_16_001217_create_payment_gateways_table.php`
- `2026_06_16_001218_create_printers_table.php`
- `2026_06_16_001219_create_stock_transfers_table.php`
- `2026_06_16_001220_create_stock_transfer_items_table.php`
- `2026_06_16_001221_create_reorder_rules_table.php`
- `2026_06_16_001222_create_tax_invoices_table.php`
- `2026_06_16_001223_create_loyalty_programs_table.php`
- `2026_06_16_001224_create_loyalty_cards_table.php`
- `2026_06_16_001225_create_loyalty_transactions_table.php`
- `2026_06_16_001226_create_loyalty_rewards_table.php`
- `2026_06_16_001227_create_report_exports_table.php`

### Models (12)
- `CurrencyRate`, `PaymentGateway`, `Printer`, `StockTransfer`, `StockTransferItem`, `ReorderRule`, `TaxInvoice`, `LoyaltyProgram`, `LoyaltyCard`, `LoyaltyTransaction`, `LoyaltyReward`, `ReportExport`

### Form Requests (21)
- Store/Update pairs for: CurrencyRate, PaymentGateway, Printer, StockTransfer, ReorderRule, TaxInvoice, LoyaltyProgram, LoyaltyCard, LoyaltyReward, ReportExport
- Store only: LoyaltyTransaction

### Services (2)
- `StockTransferService` — dispatch/receive/cancel logic with stock movement
- `LoyaltyService` — earn/burn/adjust points with balance validation

### Controllers (10)
- `CurrencyRateController`, `PaymentGatewayController`, `PrinterController`, `StockTransferController`, `ReorderRuleController`, `TaxInvoiceController`, `LoyaltyProgramController`, `LoyaltyCardController`, `LoyaltyRewardController`, `ReportExportController`

### Routes (9 files + api.php wiring)
- `currency-rates.php`, `payment-gateways.php`, `printers.php`, `stock-transfers.php`, `reorder-rules.php`, `tax-invoices.php`, `loyalty.php`, `report-exports.php`, `finances.php`
- All wired into `routes/api.php` with proper prefixes

### Frontend (2 files)
- `ui/src/types/index.ts` — shared types (`ApiResponse<T>`, `Currency`, etc.)
- `ui/src/app/pages/dashboards/admin/components/PeriodFilterBar.tsx` — reusable period filter component
