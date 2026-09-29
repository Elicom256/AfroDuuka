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

### 3. Add backup/restore strategy
- **Issue:** No backup or restore mechanism exists
- **Fix:** Implement database backup command and restore endpoint

### 4. Add stock adjustment workflow
- **Issue:** No stock adjustment workflow exists
- **Fix:** Create API endpoints for stock adjustments with audit trail

### 5. Add expiry tracking
- **Issue:** No product expiry tracking implemented
- **Fix:** Add expiry_date field to products, expiry alerts, and reporting

### 6. Add damaged/lost stock logs
- **Issue:** No tracking for damaged or lost stock
- **Fix:** Create stock damage/loss logging with reasons and approval flow

### 7. Add branch-to-branch stock transfer validation
- **Issue:** Stock transfers between branches lack validation
- **Fix:** Implement transfer workflow with source/destination validation

### 8. Add CI/CD pipeline
- **Issue:** No CI/CD pipeline configured
- **Fix:** Set up GitHub Actions for tests, linting, and deployment

### 9. Add production deployment setup
- **Issue:** No production deployment configuration
- **Fix:** Docker compose, environment configs, deployment scripts

---

## UI Tasks (After API)

### 10. Build Operations Inventory Page
- **File:** `ui/src/app/pages/dashboards/Operations/pages/OperationsInventoryPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build real inventory table with tracking components

### 11. Build Operations Analytics Page
- **File:** `ui/src/app/pages/dashboards/operations/pages/OperationsAnalyticsPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build charts and analytics components

### 12. Build Staff Sales Overview Page
- **File:** `ui/src/app/pages/dashboards/staff/pages/StaffSalesOverviewPage.tsx:17`
- **Issue:** Renders "will be implemented here" placeholder
- **Fix:** Build charts and sales flow visualization

### 13. Replace Executive dummy-data pages
- **File:** `ui/src/app/pages/dashboards/executive/pages/executive-placeholder-pages.tsx`
- **Issue:** 7 pages with hardcoded fake data (Customers, Analytics, Reports, Finances, Suppliers, Promotions, Coupons)
- **Fix:** Replace with real API-backed components or remove from navigation

### 14. Fix Executive Messages Page
- **File:** `ui/src/app/pages/dashboards/executive/pages/ExecutiveMessagesPage.tsx:104`
- **Issue:** Shows placeholder conversations
- **Fix:** Wire to real messaging API or remove page

### 15. Wire Loyalty into checkout
- **Issue:** Loyalty program not integrated into checkout flow
- **Fix:** Add loyalty points earning/redemption to POS checkout

### 16. Remove console.log statements
- **Issue:** 96 `console.log` statements in production UI
- **Fix:** Remove or replace with proper logging

### 17. Polish remaining dashboard placeholders
- **Issue:** Various "to be implemented later" states remain
- **Fix:** Complete or remove all placeholder states
