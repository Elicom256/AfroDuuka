# Basic Remaining — What's Missing for a Modern System

Below are the critical features absent from the current system. Each is described as **What | Why | How** so you can prioritize implementation before onboarding real business users.

---

## 1. Returns / Refunds Module

| | |
|---|---|
| **What** | Dedicated sales return and purchase return workflows — not just a CashFlow `refund` type. Tracks returned items, reason codes, condition (damaged/restockable), restocking, and replacement/exchange. |
| **Why** | Every retailer handles returns. Without a proper module, returns create inventory inaccuracies (stock is not auto-restocked), financial gaps (refund not linked to original transaction), and no audit trail for loss prevention. |
| **How** | Create `SaleReturn` / `SaleReturnItem` and `PurchaseReturn` / `PurchaseReturnItem` models, polymorphic `StockMovement` references, API endpoints, and admin/staff pages. Add business setting to toggle returns module. |

---

## 2. Dedicated POS (Point of Sale) Interface

| | |
|---|---|
| **What** | A fast, touch-optimized checkout screen separate from the admin sales table. Barcode search, product grid, cart sidebar, quick customer lookup, multi-payment split, receipt preview, and printer trigger. |
| **Why** | The current `AddSale` dialog is too slow for counter service. Staff need a sub-second scan → checkout → print flow. Without it, the system feels like a back-office tool, not a daily-use POS. |
| **How** | Build a new `/pos` route with a full-screen layout. Use barcode input as primary product lookup, cart state management, inline payment capture, and direct thermal printer integration via the existing `Printer` model. Staff dashboard already exists — add a "POS" link to it. |

---

## 3. Document / Image Attachments

| | |
|---|---|
| **What** | A polymorphic `Attachment` model allowing file/image uploads on Products, Purchases, Sales, Customers, Suppliers, and Workers. Includes product images (gallery), supplier contracts, purchase receipts, customer ID scans, and worker documents. |
| **Why** | No product images in a 2026 retail system is a glaring gap. Businesses need to attach receipts, contracts, and ID scans for compliance, audit, and operational reference. Currently `ReportExport` is the only model with a `file_path`. |
| **How** | Create `attachments` table with `attachable_type`, `attachable_id`, `file_path`, `file_name`, `mime_type`, `size`, `type` (image/document). Use Laravel's Filesystem (S3/local). Add upload UI components (dropzone, gallery, file list) reusable across all entities. |

---

## 4. Quotations / Proforma Invoices

| | |
|---|---|
| **What** | Generate quotes for customers that can be marked as Draft → Sent → Accepted → Converted to Sale (or Expired). Line items mirror SaleItems but are non-binding until converted. |
| **Why** | B2B and wholesale customers expect quotes before purchasing. Without quotes, sales staff resort to manual estimates or lose business. A "convert to sale" button creates a seamless lead-to-cash flow. |
| **How** | Create `Quotation` and `QuotationItem` models mirroring `Sale`/`SaleItem` with an added `status` field. API: CRUD + `convert/{id}` which creates a Sale from the quotation. Admin/Manager dashboard pages. |

---

## 5. Customer Communication (Email / SMS)

| | |
|---|---|
| **What** | Automated sending of receipts, invoices, payment reminders, and promotional messages via email and SMS. Communication log per customer. LowStockNotification exists but is unused — build on top of it. |
| **Why** | Modern customers expect digital receipts and payment reminders. Businesses need to notify customers about promotions, collect payments, and send invoices — all from within the system. Manual WhatsApp isn't scalable. |
| **How** | Create `CommunicationLog` model. Integrate mail provider (Mailgun/SMTP) and SMS provider (Twilio/Africastalking). Trigger on sale completion, payment due, promotion activation. Add per-business settings for templates and provider config. |

---

## 6. Expense Management

| | |
|---|---|
| **What** | Dedicated expense tracking beyond the generic CashFlow `expense` type. Expense categories (rent, utilities, salaries, marketing, transport), recurring expenses, expense approvals, receipt attachment, and expense vs budget reporting. |
| **Why** | CashFlow aggregates everything (sales, purchases, expenses, transfers) into one table, making expense-specific reporting difficult. Businesses need to track operational costs separately to calculate true profit margins. |
| **How** | Create `ExpenseCategory` and `Expense` models. API CRUD with filters by category, date range, branch. Admin page with expense reports and budget tracking. Link expenses to CashFlow as a sub-type. |

---

## 7. Warehouse / Stock Location Management

| | |
|---|---|
| **What** | Storage locations (warehouses, shelves, bins) within a branch. Each `BusinessBranchProduct` can be assigned to one or more locations with quantity per location. Supports picking and stock counting. |
| **Why** | Staff waste time searching for products. Without locations, stock counting is disorganized, and picking for orders or transfers is slow. Essential for any business with more than ~50 SKUs. |
| **How** | Create `StockLocation` model (branch_id, name, type: warehouse/shelf/bin). Add `location_id` to `BusinessBranchProduct` or create a pivot `inventory_locations` table. Update stock movements to track location changes. |

---

## 8. Serial / Batch Number Tracking

| | |
|---|---|
| **What** | Track inventory by serial number (electronics, appliances) or batch/lot number (perishables, pharmaceuticals). Each unit or batch is traceable from purchase → stock → sale → return. |
| **Why** | Required for warranty management, expiry tracking, product recalls, and compliance (especially in regulated industries). Without it, the system can't support electronics, pharma, or FMCG businesses. |
| **How** | Add `track_serial` and `track_batch` flags to `Product`. Create `SerialNumber` and `Batch` models. Extend `SaleItem`, `PurchaseItem`, `StockMovement` to capture serials/batches. Add UI for serial entry at sale and batch assignment at purchase. |

---

## 9. Price History

| | |
|---|---|
| **What** | Track changes to cost price and selling price over time for each `BusinessBranchProduct`. Includes who changed it, when, and the previous/next values. |
| **Why** | Without price history, margin analysis is impossible and price disputes cannot be resolved. Businesses need to understand pricing trends and audit price changes. |
| **How** | Create `PriceHistory` model (business_branch_product_id, field: cost_price/price, old_value, new_value, changed_by). Hook into model `updated` events via observer. Display price timeline on product detail pages. |

---

## 10. Payment Reconciliation

| | |
|---|---|
| **What** | Match bank statements / mobile money transaction logs against system transactions (sales, purchases, expenses). Flag unmatched items. Support manual matching and auto-suggest. |
| **Why** | Businesses using mobile money (MTN MoMo, Airtel Money) receive dozens of daily payments. Manually reconciling these against sales records is error-prone and time-consuming. A reconciliation module closes the loop between payment collection and order fulfillment. |
| **How** | Create `Reconciliation` and `ReconciliationItem` models. Build a statement upload/import flow (CSV/API). Match logic by amount, date, and reference. Admin reconciliation page with match/unmatch actions. |

---

## Priority Summary

| Priority | Feature | Effort | Impact |
|---|---|---|---|
| **P0** | Returns / Refunds | Medium | High |
| **P0** | Dedicated POS Interface | Large | Very High |
| **P0** | Document / Image Attachments | Medium | High |
| **P1** | Quotations / Proforma Invoices | Medium | High |
| **P1** | Customer Communication (Email/SMS) | Medium | High |
| **P1** | Expense Management | Small | Medium |
| **P2** | Warehouse / Stock Locations | Medium | Medium |
| **P2** | Serial / Batch Tracking | Large | Medium |
| **P2** | Price History | Small | Medium |
| **P2** | Payment Reconciliation | Large | Medium |
