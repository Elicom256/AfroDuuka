# Today's Work — DuukaFlow

## API Tasks (Start Here)

### 1. Fix FinanceController branch scope bypass
- **File:** `api/app/Http/Controllers/FinanceController.php:24`
- **Issue:** `resolveBranchId()` returns `null` when no `branch_id` is provided, causing finance endpoints to return unfiltered cross-branch data
- **Fix:** Return the user's default branch ID instead of `null`, or require `branch_id` for branch-scoped roles
- **Status:** DONE

### 2. Remove dead code — `sendDemoMessage()`
- **File:** `api/app/Services/WhatsAppService.php`
- **Issue:** `sendDemoMessage()` has no callers, duplicates `queueDemoMessage()`, and carries its own copy of the `$result['success']` bug
- **Fix:** Delete the method entirely
- **Status:** DONE

### 3. Add backup/restore strategy
- **Issue:** No backup or restore mechanism exists
- **Fix:** Implement database backup command and restore endpoint
- **Status:** DONE — already exists at `api/app/Console/Commands/DatabaseBackup.php` (pg_dump/pg_restore with --force)

### 4. Add stock adjustment workflow
- **Issue:** No stock adjustment workflow exists
- **Fix:** Create API endpoints for stock adjustments with audit trail
- **Status:** DONE — already exists via `InventoryService::adjust()` and `ProductController::adjustStock()` with reason codes (damaged, expired, lost, stock_take)

### 5. Add expiry tracking
- **Issue:** No product expiry tracking implemented
- **Fix:** Add expiry_date field to products, expiry alerts, and reporting
- **Status:** DONE — already exists: `SweepExpiredProducts` command, `ProductController::expiringAnalytics()`, `expiry_date` field on products

### 6. Add damaged/lost stock logs
- **Issue:** No tracking for damaged or lost stock
- **Fix:** Create stock damage/loss logging with reasons and approval flow
- **Status:** DONE — already exists: `ProductLossController`, `InventoryService::writeOff()` with reason codes (damaged, expired, lost)

### 7. Add branch-to-branch stock transfer validation
- **Issue:** Stock transfers between branches lack validation
- **Fix:** Implement transfer workflow with source/destination validation
- **Status:** DONE — already exists: `StockTransferController` with dispatch/receive/cancel workflow via `StockTransferService`

### 8. Add CI/CD pipeline
- **Issue:** No CI/CD pipeline configured
- **Fix:** Set up GitHub Actions for tests, linting, and deployment
- **Status:** DONE — created `.github/workflows/ci.yml` with backend (Pint + PHPUnit) and frontend (ESLint + build) jobs

### 9. Add production deployment setup
- **Issue:** No production deployment configuration
- **Fix:** Docker compose, environment configs, deployment scripts
- **Status:** DONE — already exists: `docker-compose.prod.yml`, `Dockerfile` (api + ui), `.env.production.example`

---

## UI Tasks (After API)

### 10. Build Operations Inventory Page
- **File:** `ui/src/app/pages/dashboards/operations/pages/OperationsInventoryPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build real inventory table with tracking components
- **Status:** DONE — built with summary cards, ProductTable, AdjustStock dialog, and OperationsStockAlerts

### 11. Build Operations Analytics Page
- **File:** `ui/src/app/pages/dashboards/operations/pages/OperationsAnalyticsPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build charts and analytics components
- **Status:** DONE — built with KPI cards, Doughnut chart (stock status), Bar chart (stock movement), top selling products, low/out of stock lists using real API data

### 12. Build Staff Sales Overview Page
- **File:** `ui/src/app/pages/dashboards/staff/pages/StaffSalesOverviewPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build charts and sales flow visualization
- **Status:** DONE — built with KPI cards, revenue trend line chart, daily orders bar chart, top selling products, recent sales, low/out of stock alerts using real API data

### 13. Replace Executive dummy-data pages
- **File:** `ui/src/app/pages/dashboards/executive/pages/executive-placeholder-pages.tsx`
- **Issue:** 7 pages with hardcoded fake data (Customers, Analytics, Reports, Finances, Suppliers, Promotions, Coupons)
- **Fix:** Replace with real API-backed components or remove from navigation
- **Status:** DONE — all 7 pages now use real API queries (customers, suppliers, promotions, analytics, cash flow, reports)

### 14. Fix Executive Messages Page
- **File:** `ui/src/app/pages/dashboards/executive/pages/ExecutiveMessagesPage.tsx:104`
- **Issue:** Shows placeholder conversations
- **Fix:** Wire to real messaging API or remove page
- **Status:** DONE — wired to `useBranchMessagesQuery` and `useGetNotificationsQuery` with real conversation list, message preview, and mark-all-read

### 15. Remove Loyalty module
- **Status:** DONE — all loyalty files deleted, references removed from store, routes, sidebars, and API

### 16. Remove console.log statements
- **Issue:** 96 `console.log` statements in production UI
- **Fix:** Remove or replace with proper logging

### 17. Polish remaining dashboard placeholders
- **Issue:** Various "to be implemented later" states remain
- **Fix:** Complete or remove all placeholder states
