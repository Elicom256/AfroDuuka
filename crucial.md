# Crucial — What Must Be Done Before Launch

**Note**: Many items from `basicremaining.md` have already been implemented (Returns/Refunds, POS, Expenses, Price History). Below is the current gap analysis based on actual codebase audit.

---

## P0 — Must Fix Before Onboarding Users

| # | Module | Issue | Why It Blocks Launch |
|---|--------|-------|---------------------|
| 1 | **Promotions** | `PromotionController` has empty stubs — CRUD methods do nothing. No API routes wired. Frontend shows dummy data. | Module is advertised in the UI but completely broken. Cannot launch with a broken module. |
| 2 | **Coupons** | No model, no controller, no API exists. Frontend page shows hardcoded dummy data. | Same as Promotions — visible in sidebar but non-functional. |
| 3 | **Inventory Page** (Admin) | `AdminInventoryPage.tsx` renders hardcoded static data, not real API data. | Core inventory view is non-functional for admin users. |
| 4 | **Analytics Page** (Admin) | `AdminAnalyticsPage` uses real API calls (`useGetSalesAnalyticsQuery`, `useCashFlowAnalyticsQuery`, `useProductAnalyticsQuery`). **Already works.** | ✅ Already good |
| 5 | **Finances Page** (Admin) | `AdminFinancesPage` uses `useGetCashFlowsQuery` and renders `CashFlowAnalytics` + `CashFlowTable`. **Already works.** | ✅ Already good |
| 6 | **Todos** | `todos.php` routes are fully commented out. `api.php` does not mount todos. Backend controller exists but is unreachable. | Frontend Todo list/filter will always fail on API calls. |
| 7 | **Document / Image Attachments** | No polymorphic `Attachment` model. No product images. No file upload support anywhere except `ReportExport`. | No product images in a 2026 retail system is unacceptable for first impressions. Also blocks receipt/contract uploads. |

## P1 — Important for Real-World Use

| # | Module | Issue | Why It Matters |
|---|--------|-------|---------------|
| 8 | **Customer Communication (Email/SMS)** | No `CommunicationLog` model. No email or SMS integration. Receipts are in-app only; no digital delivery. | Customers expect digital receipts. Businesses need to send payment reminders and promo messages. |
| 9 | **Quotations / Proforma Invoices** | No `Quotation` or `QuotationItem` models. No API or UI. | B2B and wholesale customers expect quotes before purchasing. Without quotes, sales staff resort to manual workarounds. |
| 10 | **Orders Page** | `AdminOrdersPage.tsx` shows hardcoded dummy data. No backend orders system exists. | Visible in sidebar but non-functional. Either build it or remove from navigation. |
| 11 | **History Page** | `AdminHistoryPage.tsx` shows hardcoded dummy data. No dedicated history backend. | Visible in sidebar but non-functional. Either build it or remove from navigation. |

## P2 — Enhancements for Polished Launch

| # | Module | Issue |
|---|--------|-------|
| 12 | **Warehouse / Stock Locations** | No `StockLocation` model. Products have no shelf/bin/warehouse assignment. |
| 13 | **Serial / Batch Number Tracking** | No `SerialNumber` or `Batch` models. No serialized inventory support. |
| 14 | **Payment Reconciliation** | No `Reconciliation` model. No bank/mobile money statement matching. |
| 15 | **History / Activity Feed** | No dedicated endpoint for user-facing history (backend has `ActivityLog` but no frontend integration). |

## What's Already Good (Implemented)

These from `basicremaining.md` are done:
- ✅ Returns/Refunds (SaleReturn, PurchaseReturn with restocking)
- ✅ Dedicated POS Interface (full-screen PosPage with barcode, hold/resume, split payments)
- ✅ Expense Management (Expense, ExpenseCategory with approve workflow, charts)
- ✅ Price History (model, analytics, timeline chart)
- ✅ Stock Transfers (dispatch/receive/cancel workflow)
- ✅ Loyalty Programs (cards, earn/burn, rewards)
- ✅ Reorder Rules (with low-stock alerts)
- ✅ Tax Invoices (with URA submission)
- ✅ Receipts (with PDF generation)
- ✅ Printers (thermal printer config)

## Recommended Launch Checklist

### Week 1: Fix P0 Broken Modules
1. Wire up `Promotion` controller + routes OR hide from UI
2. Wire up `Coupon` controller + routes OR hide from UI
3. Connect `AdminInventoryPage` to real API
6. Uncomment todo routes in `api.php` and `todos.php`

### Week 2: Product Images & Attachments
7. Create `Attachment` polymorphic model + migration
8. Add image upload to Product create/edit
9. Add file upload to Purchases, Customers, Suppliers, Workers

### Week 3: Core Business Features
10. Build `Quotation` + `QuotationItem` models, API, and UI
11. Integrate email/SMS for receipts and notifications
12. Fix or remove Orders and History placeholder pages

### Week 4: Polish & Testing
13. End-to-end testing of all P0/P1 flows
14. Role-based access verification (admin/manager/staff)
15. Performance testing on POS checkout flow
