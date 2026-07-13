# MODULE 3 — Expense Management

Implement dedicated expense management instead of relying only on generic CashFlow.

## Backend

**Create:**

- ExpenseCategory
- Expense
- Fields include:
  -> Category, Amount, Branch, Business, Vendor, Description, Receipt attachment reference, Recurring, flag, Payment date, Status, Created by

- Approval status (optional if permissions already exist)
- Automatically generate related CashFlow records.
- Provide reporting endpoints.
- Filtering:
  ->Date, Branch, Category, Status, Search

## Frontend

**Pages:**

- Expense Categories
- Expenses

**Features:**
-> CRUD
-> Approve (if permitted)
-> Filters
-> Totals
-> Monthly summaries
-> Export integration
-> Charts using existing chart components

## Constraints

- Do not hallucinate
- Scalability is key
- Work everything from api to ui with proper route naming
