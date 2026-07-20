# NEW MODULE
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/app/hooks/useCurrency';
import { TrendingUp, TrendingDown, DollarSign, Wallet } from 'lucide-react';
import { cn } from '@/lib/utils'; // assuming you have this from shadcn

type FinanceSummaryCardsProps = {
  total_revenue: number;
  total_expenses: number;
  net_profit: number;
  cash_balance: number;
};

export const FinanceSummaryCards = ({
  total_revenue,
  total_expenses,
  net_profit,
  cash_balance,
}: FinanceSummaryCardsProps) => {
  const { currency } = useCurrency();
  const isProfitPositive = net_profit >= 0;

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(Math.abs(amount));
  };

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {/* Revenue */}
      <Card className="group border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
            Total Revenue
          </CardTitle>
          <div className="rounded-full bg-emerald-100 p-1.5 dark:bg-emerald-900">
            <TrendingUp className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-emerald-700 dark:text-emerald-300">
            {currency} {formatCurrency(total_revenue)}
          </p>
        </CardContent>
      </Card>

      {/* Expenses */}
      <Card className="group border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-red-700 dark:text-red-400">
            Total Expenses
          </CardTitle>
          <div className="rounded-full bg-red-100 p-1.5 dark:bg-red-900">
            <TrendingDown className="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-red-700 dark:text-red-300">
            {currency} {formatCurrency(total_expenses)}
          </p>
        </CardContent>
      </Card>

      {/* Net Profit */}
      <Card
        className={cn(
          'group transition-all hover:shadow-md hover:-translate-y-0.5',
          isProfitPositive
            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/50'
            : 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50'
        )}
      >
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle
            className={cn(
              'text-sm font-medium',
              isProfitPositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'
            )}
          >
            Net Profit
          </CardTitle>
          <div
            className={cn(
              'rounded-full p-1.5',
              isProfitPositive ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-red-100 dark:bg-red-900'
            )}
          >
            <DollarSign
              className={cn(
                'h-5 w-5',
                isProfitPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'
              )}
            />
          </div>
        </CardHeader>
        <CardContent>
          <p
            className={cn(
              'text-3xl font-semibold tracking-tight',
              isProfitPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'
            )}
          >
            {currency} {formatCurrency(net_profit)}
          </p>
          {isProfitPositive ? (
            <p className="mt-1 text-xs text-emerald-600 flex items-center gap-1 dark:text-emerald-400">
              <TrendingUp className="h-3 w-3" /> Positive
            </p>
          ) : (
            <p className="mt-1 text-xs text-red-600 flex items-center gap-1 dark:text-red-400">
              <TrendingDown className="h-3 w-3" /> Loss
            </p>
          )}
        </CardContent>
      </Card>

      {/* Cash Balance */}
      <Card className="group border-purple-200 bg-purple-50/50 dark:border-purple-900 dark:bg-purple-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-purple-700 dark:text-purple-400">
            Cash Balance
          </CardTitle>
          <div className="rounded-full bg-purple-100 p-1.5 dark:bg-purple-900">
            <Wallet className="h-5 w-5 text-purple-600 dark:text-purple-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-purple-700 dark:text-purple-300">
            {currency} {formatCurrency(cash_balance)}
          </p>
        </CardContent>
      </Card>
    </div>
  );
};
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
