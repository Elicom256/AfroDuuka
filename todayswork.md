# NEW MODULE

Implement a complete **Finance Module** by following the existing project architecture, coding standards, API conventions, UI design, routing structure, authorization system, and reusable components.

This project consists of:

- Laravel REST API
- React (TypeScript) SPA
- shadcn/ui
- lucide-react
- Existing authentication and role-based authorization
- Existing Business and Business Branch architecture

Do **not** introduce a different architecture.

Inspect the existing codebase first and reuse existing:

- services
- patterns
- middleware
- policies
- helpers
- layouts
- components
- API response structure
- validation
- pagination
- filters
- reporting patterns

---

# FINANCE MODULE

Implement a complete financial module that tracks every financial movement occurring within the system.

The module should support both:

- Business-level finances
- Business Branch-level finances

Financial information should automatically update as users perform normal operations throughout the system.

The Finance module should become the single source of truth for financial reporting.

---

# Objectives

Track financial movements generated from:

- Sales
- Purchases
- Sales Returns
- Purchase Returns
- Expenses
- Tax Payments
- Customer Payments
- Supplier Payments
- Opening Balances
- Cash Adjustments
- Manual Adjustments (Admin only)
- Future financial transaction types

The module should be extensible for future accounting features.

---

# Backend

Implement:

- FinancialTransaction
- FinancialAccount (if appropriate based on existing architecture)

Every financial event should create a Financial Transaction.

Suggested fields include:

- business_id
- business_branch_id
- reference_type
- reference_id
- transaction_type
- direction (Credit / Debit)
- amount
- running_balance
- payment_method_id
- description
- notes
- transaction_date
- performed_by
- timestamps

Do not duplicate existing business logic.

Reuse existing Sales, Purchases, Expenses, Returns, and Payment workflows.

Financial transactions should be generated automatically whenever those modules perform actions.

---

# Financial Movements

Examples include:

Sales

- Revenue
- Customer Payments

Purchases

- Inventory Purchases
- Supplier Payments

Sales Returns

- Customer Refunds

Purchase Returns

- Supplier Refunds

Expenses

- Operational Expenses

Taxes

- Tax Payments

Opening Balance

Manual Cash Adjustment

Any future module that affects finances.

---

# Integration

Integrate with the existing:

- Sales
- Purchases
- Returns
- Expenses
- Payment Methods
- Business
- Business Branch

Do not require duplicate data entry.

The module should automatically record financial movements whenever existing operations are performed.

---

# Business vs Branch

Track finances at both levels.

Business

Aggregate all branch financial movements.

Business Branch

Track only transactions belonging to that branch.

The Business totals should always reflect the sum of all branch financial activity.

---

# Reports

Support generating:

- Cash Flow
- Revenue Report
- Expense Report
- Income Summary
- Branch Financial Statement
- Business Financial Statement
- Financial Transaction History
- Date Range Reports
- Payment Method Reports

Reuse existing report architecture where applicable.

---

# Dashboard Integration

Update existing dashboards to include financial summaries.

Business Dashboard

- Total Revenue
- Total Expenses
- Net Profit
- Cash Balance
- Recent Transactions

Branch Dashboard

- Branch Revenue
- Branch Expenses
- Branch Cash Balance
- Recent Transactions

---

# Frontend

Implement the complete frontend.

Create all necessary:

- Pages
- Components
- Forms
- Tables
- Dialogs
- Filters
- Search
- Pagination
- API hooks/services
- Types
- Routes
- Navigation

Reuse existing reusable components.

---

# Pages

Implement:

Finance Dashboard

Financial Transactions

View Transaction

Manual Adjustment (Admin)

Cash Flow

Revenue Report

Expense Report

Branch Financial Statement

Business Financial Statement

Financial Reports

---

# Permissions

Use the existing authorization system.

## Admin

Can:

- View business finances
- View every branch
- View aggregate business finances
- Query any financial movement
- Generate reports
- Perform manual adjustments
- View all dashboards

## Branch Manager

Can:

- View only their branch
- View branch reports
- View branch transactions

Cannot:

- View other branches
- View business-wide finances

## Cashier

Can:

- View only their assigned branch financial information where appropriate
- View transactions related to their work

Cannot:

- View other branches
- View business-wide reports
- Perform manual financial adjustments

---

# API

Follow existing API conventions.

Implement REST endpoints including:

- index
- show
- store (where appropriate)
- update (where appropriate)
- destroy (only if project conventions allow)

Use:

- Form Requests
- Resources
- Policies
- Validation
- Database Transactions

Return responses using the existing API response structure.

---

# Navigation

Integrate naturally into the existing sidebar.

Suggested section:

Finance

- Dashboard
- Transactions
- Cash Flow
- Reports

Do not redesign the navigation.

Follow the existing layout.

---

# UI

Reuse existing:

- shadcn/ui components
- lucide-react icons
- Cards
- Tables
- Dialogs
- Sheets
- Dropdowns
- Filters
- Pagination
- Empty states
- Loading states
- Skeletons
- Charts already used elsewhere in the project

Maintain complete UI consistency.

---

# Constraints

- Maintain the current architecture.
- Do not duplicate business logic.
- Follow the existing project structure.
- Reuse existing services whenever possible.
- Use database transactions where appropriate.
- Follow RESTful conventions.
- Wire the complete backend and frontend.
- Maintain production-quality code.
- Reuse existing helpers, utilities, UI components, middleware, and packages instead of introducing unnecessary abstractions.
