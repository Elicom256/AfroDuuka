Implement the following four production-ready modules in the existing Laravel + React inventory/POS system. Follow the existing project architecture, coding style, authorization, routing, validation, business scoping, UI components, API conventions, and naming patterns. Do not introduce inconsistent patterns.

The implementation must include:

* Backend (Models, Migrations, Controllers, Requests, Policies, Resources, Services where appropriate)
* Frontend (React pages, dialogs, hooks, API integration, reusable components)
* Validation
* Authorization
* Audit logging where applicable
* Feature tests and unit tests where appropriate (here create the front end folder for tests)
* Database seed updates if needed
* Navigation/menu integration
* Dashboard integration where relevant

---

# MODULE 1 — Dedicated POS

Create a production-grade POS interface optimized for speed.

## Backend

Implement APIs for:

* Product search by:

  * Barcode
  * SKU
  * Product name
* Customer search
* Add sale
* Hold sale
* Resume held sale
* Void sale
* Complete sale
* Receipt generation
* Stock validation before checkout
* Automatic stock deduction
* Cash drawer transaction creation if already supported
* Printer integration using the existing Printer model

Respect business, branch and user permissions.

## Frontend

Create a full-screen `/pos` page.

Features:

* Barcode input autofocus
* Product search
* Touch-friendly product grid
* Cart sidebar
* Quantity editing
* Discount editing
* Tax display
* Customer selection
* Walk-in customer
* Multiple payment methods
* Running totals
* Checkout dialog
* Receipt preview
* Print receipt
* Hold sale
* Resume held sale
* Keyboard shortcuts
* Fast workflow suitable for supermarket checkout

The interface must not reuse the admin CRUD table.

---

# MODULE 2 — Returns / Refunds

Implement complete sales return and purchase return workflows.

## Backend

Create:

SaleReturn
SaleReturnItem

PurchaseReturn
PurchaseReturnItem

Fields include:

* original transaction
* reason
* notes
* quantity
* refund amount
* restock flag
* condition
* processed_by
* timestamps

When processing returns:

Sales Return

* validate original sale
* prevent returning more than sold
* restore stock if restock=true
* create stock movements
* create cashflow adjustment
* preserve audit trail

Purchase Return

* reduce inventory
* update supplier balances where appropriate
* create stock movements
* create cashflow adjustment

Never delete inventory history.

## Frontend

Create pages for:

Sales Returns

Purchase Returns

Features:

* Search original invoice
* Select returned items
* Partial returns
* Full returns
* Return reason
* Condition
* Refund amount
* Notes
* Confirmation dialog
* Return history
* Printable return receipt

---

# MODULE 3 — Expense Management

Implement dedicated expense management instead of relying only on generic CashFlow.

## Backend

Create:

ExpenseCategory

Expense

Fields include:

Category

Amount

Branch

Business

Vendor

Description

Receipt attachment reference

Recurring flag

Payment date

Status

Created by

Approval status (optional if permissions already exist)

Automatically generate related CashFlow records.

Provide reporting endpoints.

Filtering:

Date

Branch

Category

Status

Search

## Frontend

Pages:

Expense Categories

Expenses

Features:

Create

Edit

Delete

Approve (if permitted)

Filters

Totals

Monthly summaries

Export integration

Receipt upload if attachment module already exists

Charts using existing chart components

---

# MODULE 4 — Price History

Track every product price change.

## Backend

Create:

PriceHistory

Fields:

business_branch_product_id

old_cost_price

new_cost_price

old_sale_price

new_sale_price

changed_by

change_reason (optional)

timestamps

Automatically record history whenever prices change.

Use observers/events.

Never overwrite historical records.

Expose API:

Product price timeline

Latest changes

Filter by product

Filter by date

## Frontend

On Product Details add:

Price History tab

Timeline

Table showing:

Date

Old Cost

New Cost

Old Selling

New Selling

User

Reason

Include pagination.

---

# GENERAL REQUIREMENTS

Maintain existing architecture.

Reuse existing components whenever possible.

Do not duplicate logic.

Respect:

Business scoping

Branch scoping

Permissions

Soft deletes

Audit logging

Notifications

Activity logs

Transactions for all critical writes.

Ensure all stock movements remain consistent.

Use eager loading appropriately.

Prevent N+1 queries.

Validate all user input.

Return consistent API resources.

Follow existing naming conventions.

Provide responsive UI.

Use existing design system.

Do not introduce breaking changes.

At the end, provide:

1. Database schema summary
2. API endpoint summary
3. Frontend pages added
4. Components created
5. Files modified
6. Suggested follow-up improvements


### Constraints

- Do not hallucinate
- Check the possible outcome before you code
- Implement scalable approaches
- Include comments in your work
- Ensure the end points are matched well with the backend routes
