# Notes found along the way

Things the chunks turned up that are **not** fixed, plus decisions taken that are worth
remembering. Each entry says why it was left alone, so the next person does not have to
rediscover the reasoning.

---

## Chunk 1 — transactions and reports

### Nobody can be named from a person, a customer, or a supplier

`createdBy` eager-loads into the `created_by` key, which is also the foreign-key column, so
the loaded user replaces the raw id. `Customer` and `Supplier` have no name column at all: a
person's name is `firstname` + `lastname` on the user behind them, a company is
`company_name`, and `Customer::name()` is a plain method so it never reaches the JSON.

Fixed for the transaction detail page in chunk 1 and for the audit pages in chunk 3. This is
the same fault as item 5 in the ranking, and it is **probably not the last place it
happens**. Worth a sweep for `?.name` across the UI once the pattern is confirmed.

### `running_balance` is not on the transaction detail page

Only `FinanceService` attaches it, and only to a list. The detail page shows no running
balance rather than inventing one. If a per-row balance is wanted on a single transaction,
that is a new requirement, not a bug.

### `ExecutiveFinancesPage.tsx` is dead code

Imported nowhere and not routed. The "View All Transactions" button on it is unreachable.
Left alone because deleting a file is a separate decision from fixing a bug.

### Expenses are missing from analytics

Analytics read `cash_flows` only. `expenses` rows are mirrored into the ledger only via
`ExpenseController::store()`, so the seeder (which calls `Expense::firstOrCreate` directly),
updates and deletes all desync. This is item 18 in the ranking and is deliberately not
fixed in this chunk — it is a data-model decision, not a filter tweak.

### `todosRoutes.test.tsx` failed on a clean checkout

`renders the todos page for Executive` failed identically with every change stashed, so it
predates chunk 1. It did not reproduce in chunks 2 and 3 and the suite is now green, but it
was never diagnosed. Treat as flaky rather than fixed.

---

## Chunk 2 — product audit list, activity logging

### The audit dialogs' branch mismatch

`GET /api/products` takes no branch parameter. The audit dialog's branch dropdown does.
`StoreProductAuditRequest` validates `items.*.product_id` with a bare `exists:products,id`,
and `ProductAuditService::createAudit()` then does a branch-scoped `findOrFail`. For a
branch-pinned executive the list shows branch 1 and the dropdown offers branch 2, and a
cross-branch product id 404s rather than validating.

Left alone because it is item 13 (the executive branch-pinning problem) and half-fixing it
would hide a cross-cutting problem behind a green test. Fix item 13 first.

### The `log_name` taxonomy is inconsistent and nobody owns it

The working calls use English sentences (`Recorded Expense`, `Created Expense Category`).
The broken ones used snake_case (`updated_expense`, `approved_expense`). Both conventions
now coexist in the `activity_logs` table.

Unifying them is a product decision that belongs to item 9, which is specifically about
deciding what counts as an important log. Changing them in chunk 2 would have been inventing
a taxonomy.

### Product audit and financial audit approval was entirely broken

Not in bugs.md. Both `approveAudit()` methods call the log inside `DB::transaction`, so the
`BadMethodCallException` rolled the whole thing back: approving a product audit applied no
stock adjustment and changed no status, and approving a financial audit never persisted.

### `ExpenseController::destroy` read a variable it did not have

`destroy(Expense $expense)` has no `$request` parameter, so the broken call also read an
undefined variable. Two faults on the same line, both firing after the delete.

---

## Chunk 3 — audit names, analytics sixth card

### `total_products` is read but never returned

`ExecutiveAnalyticsPage` and `OperationsAnalyticsPage` read `analytics.data.total_products`,
which `ProductService::analytics()` never returned — it returns `lowStock`, `outOfStock` and
the totals. Those pages are showing zeros for a figure they were handed no key for.

Not in bugs.md, and it is not clear whether they are meant to read this endpoint or another.
Left for a decision rather than a guess.

### The top-products list was removed, not repaired

`ProductService::analytics()` returned a `topProducts` key ranking products by realised
profit. Removed because no consumer read it (every top-products component in the app reads a
`top_products` key from a different endpoint) and because it cannot be repaired as written:
`sale_items` stores no cost, so a per-sale profit can only come from the product's *current*
`cost_price`, which is not the cost the sale was made at.

A correct per-product profit needs a cost-at-sale column. That is a schema decision.

### `poorMarginProducts` had a second, independent fault

Aliased an expression as `markup_percentage` then filtered and ordered by the alias.
PostgreSQL accepts a select-list alias in `ORDER BY` but not in `HAVING` — `HAVING` is
evaluated before the list is projected — so it was a 42703; rewriting it as `HAVING` without
`GROUP BY` became a 42803 grouping error. It is a row filter, so it belongs in `WHERE`.

It had been masked behind the `topProducts` 500, which is why it only appeared once that was
removed.

---

## Chunk 4 — products table, exports

### The xlsx conversion is a dependency decision, not a bug fix

bugs.md asks for xlsx. There is no `maatwebsite/excel` anywhere — no composer entry, no vendor
dir, no `phpoffice/phpspreadsheet` — and `ExportService` is a hand-rolled CSV stream.
Switching means a new dependency, `zip` and `xml` PHP extensions in both Dockerfiles, and a
rewrite of `ExportService`. Deferred to its own chunk for sign-off.

### The export route has no `auth:sanctum`

`api/routes/api.php:226` declares the export route inline with `middleware('role')` and no
`auth:sanctum`. Sanctum's guard falls back to the configured `sanctum.guard` (default `web`),
so `Auth::user()` is null, `EffectiveBranchScope::branchesFor(null)` returns null, the branch
filter is silently skipped, and `ExportService` then calls `Auth::user()->business_id` on
null — a 500 for customers and suppliers, and a cross-tenant data leak for products, sales
and purchases. Fixed in chunk 4 alongside the 404.

### `ExportButton.tsx` hardcodes a `.csv` download name

`a.download = \`${type}-...csv\`` regardless of what the server actually sends. Will need
updating when the xlsx conversion lands.

---

## Chunk 4 — products table, exports

### The export columns did not exist

`ExportService` read `name`, `phone`, `email` and `location` off `Customer` and `Supplier`.
Neither table has those columns — only `company_name` and a `user_id`. Reading `->name` off a
loaded Eloquent model returns null rather than raising, so the export produced a file with
blank columns and **looked like it had worked**. That is worse than failing.

Fixed by exporting the columns that exist and eager-loading `user` for the contact details.
The sales and purchases exports had the same fault one level up (`customer?->name`,
`supplier?->name`).

### An unknown export type answered 500

The `match` in `ExportService::export()` threw a plain `InvalidArgumentException` for an
unknown type, which surfaces as a 500. A bad path segment is a client error. Now a 404.

### The products table delete button is gated on `canDelete`

`{canDelete && <ConfirmDeleteButton .../>}`, and `canDelete` comes from the signed-in user's
role. A test that does not sign a user in sees no delete button either way, so it cannot
tell a removed button from a hidden one. The test has to mock `useLoggedinUserQuery` with an
elevated role first.

The button is also icon-only with no accessible name, so it cannot be selected by
`getByRole('button', { name: /delete/i })`. The tests select on the lucide icon class
instead.

### The export URL fault is not backend-testable

Fault 1 (the doubled `/api`) is in the frontend, so no backend test can see it. Fault 2 (the
missing `auth:sanctum`) is invisible under `Sanctum::actingAs()`, which sets the user on the
sanctum guard as well as the web one. The customers and suppliers tests are the ones that
catch it — without `auth:sanctum`, `Auth::user()` is null and `ExportService` calls
`->business_id` on null, which is a 500. This is written into the `ExportTest` class
comment so the two products tests are not mistaken for coverage of it.
