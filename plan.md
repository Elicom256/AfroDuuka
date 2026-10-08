# Fix: the bugs in `bugs.md`, easiest first

Supersedes the POS `25P02` plan, which is complete and was verified green in
`bb11394`. One chunk at a time, pushed after each; mark the chunk `- [x]` and
rewrite the plan for the next one.

## Ranking (easiest -> hardest)

Ties broken by blast radius: cheap-and-unblocks-others ranks above
cheap-and-isolated.

| # | Bug | Root cause | Complexity | |
|---|-----|-----------|-----------|-|
| 1 | Transactions page not accessible | `RolePermissions` missing import in `FinanceController` -> 500. Also `finance/transactions/:id` route absent | LOW | **done** |
| 2 | Reports fail to load | Same missing import, same file | LOW | **done** |
| 3 | Product Audit: no products selectable | Dialogs read `products.data`; `ProductController::index()` returns a plain array | LOW | **done** |
| 4 | Approve expense writes but toast fails | `ActivityLog::log()` does not exist; throws after the un-transacted write | LOW-MED | **done** |
| 5 | Financial audit "performed by" empty | Eager-loaded relation serialises as `performed_by` holding the user, UI reads it as an id | LOW | **done** |
| 6 | Analytics: 6th card fails | `withSum` subquery references `selling_price`/`cost_price` on `sale_items`, which has neither | LOW-MED | **done** |
| 7 | Products table: delete button in wrong place | Cosmetic column in the executive products table | LOW | |
| 8 | Export failure (all exports 404) | `VITE_BASE_URL` already ends in `/api`; `ExportButton` appends a second. Route also lacks `auth:sanctum` | LOW for the 404, MED for xlsx | |
| 9 | Activity logs: noise + employee scoping | Dashboard widget passes no `log_name`; no importance rule | LOW-MED | |
| 10 | Stock transfer dispatch unique violation | `resolveDestinationProduct()` searches through the branch global scope, cannot see the destination row, blind-inserts | LOW-MED | |
| 11 | Workers: duplicate employee_code | Code generated from a tenant-scoped `count()` against a global unique index; seeded rows have `business_id = NULL` so the count is permanently 0 | LOW-MED | |
| 12 | Branches: no single-branch page | No `dashboard/branches/:id` route or summary page | MED | |
| 13 | Workers: cannot add to another branch | `EffectiveBranchScope::branchesFor()` grants all-branches only when `business_branch_id IS NULL`, and onboarding always pins the executive to one branch. Affects 39 form requests | MED | |
| 14 | Suppliers + Customers pages | Backend exists, no frontend routes under People | MED | |
| 15 | Product update on purchase | Product mutation lives on `/receive`, which the UI never calls; `selling_price` is not editable at purchase time | MED | |
| 16 | Receipt redesign (business name, logo, QR) | Template and React component never read business identity; no `business()` relation; no QR dependency; dompdf `enable_remote=false` | MED | |
| 17 | Workers: drop `business_id`/`business_branch_id` | Duplicated from `users`, actor-stamped by `BaseModel`, read by `AttendanceController` | MED | |
| 18 | Expenses missing from analytics | Analytics read `cash_flows` only; `expenses` rows are mirrored only via `ExpenseController::store()`, so seeders/updates/deletes desync | HIGH | |
| 19 | Currency rates from a live source | Fully manual today; needs provider config, an artisan sync command, and a schedule | HIGH | |
| 20 | EmployeeSalary -> Salary rename | Model, controller, requests, migrations, seeders, routes, frontend all renamed; role now drives salary | HIGH | |
| -- | Subscriptions | Explicitly deferred in bugs.md | SKIP | |

---

- [x] **Chunk 1** — Items 1 and 2: restore the transactions list and the finance
      reports, and give the transaction table a real detail page.

  One missing `use App\Support\Auth\RolePermissions;` in `FinanceController.php:10`.
  The name resolved to `App\Http\Controllers\RolePermissions`, which does not exist,
  so PHP threw an `Error` -- not an `\Exception` -- and the `catch` around all seven
  guarded methods never saw it. Every transactions and reports endpoint answered 500.
  Nothing caught it because the suite only exercised `POST /finances/adjustments`,
  which authorizes in the form request instead.

  Also in this chunk, two faults the 500 had been hiding:
  - `resolveBranchId()` compared the branch id with `in_array(..., true)` against a
    list of integers, while query strings and path segments are strings -- so asking
    for **your own** branch returned 403, which is what the transactions page's own
    branch dropdown sends.
  - `createdBy` eager-loads over the `created_by` foreign key, and `Customer` /
    `Supplier` have no name column (`Customer::name()` is a plain method, so it never
    reaches the JSON). Same class of fault as item 5, and probably not the last.

  The role gate, the branch check and the `findOrFail` moved above the `try`, so a
  refusal is a 403 and an unknown id is a 404 instead of both being flattened into
  422. `/dashboard/finance/transactions/:id` added to both role trees with a detail
  view. Table and detail share one copy of the sign logic.

  Backend 788 passed (2313 assertions). Frontend 769 passed, 1 failed --
  `todosRoutes.test.tsx`, which fails identically on a clean checkout, so it is
  pre-existing and still needs its own look.

## Chunk 2: items 3 and 4 — done

Two unrelated subsystems, both mechanical. Item 3 was two lines; item 4 was one
broken helper called from eleven places.

### Item 3: product audit had no products to select

`ProductController::index()` answers `{ message, products }` with `products` a plain
collection, so it has no `.data`. Both audit dialogs read one level too deep —
`productsData?.products?.data ?? []` — so the `?? []` swallowed the `undefined` and the
select rendered zero options. The submit guard then rejected the form, which is the
reported "this aborts product auditing". Silent: no error, just an empty dropdown.

Only those two files used the paginator-shaped accessor; every other consumer of
`GET /api/products` already read `data?.products ?? []`.

The dialogs' branch mismatch is **not** fixed here. `GET /api/products` takes no branch
parameter, the dialog's branch dropdown does, `StoreProductAuditRequest` validates
`items.*.product_id` with a bare `exists:products,id`, and
`ProductAuditService::createAudit()` then does a branch-scoped `findOrFail`. For a
branch-pinned executive the list shows branch 1 and the dropdown offers branch 2. That
is item 13, and half-fixing it now would hide a cross-cutting problem behind a green
test.

### Item 4: `ActivityLog::log()` does not exist

`App\Models\ActivityLog` extends Spatie's `Activity`, which has no static `log()`; that
method lives on the unrelated `ActivityLogger` instance class. All eleven call sites
threw `BadMethodCallException` **after** their write. Replaced with
`ActivityLogService::activity()`, which already existed, already derives the causer from
`Auth::user()` itself, and was already used correctly by the `store()` method of all
three controllers. The two services now get it injected.

What the sweep actually uncovered, beyond the reported approve-expense toast:

| Site | Consequence |
|---|---|
| `ExpenseController` update/delete/approve | reported: the row lands, the response 500s |
| `ExpenseController::destroy` | **also** read `$request` in `destroy(Expense $expense)`, which has no such parameter — a second fault on the same line |
| `ProductAuditService::approveAudit` | **inside `DB::transaction`** — approving a product audit rolled back completely, so the counted quantity was never applied to stock and the status never changed |
| `FinancialAuditService::approveAudit` | **inside `DB::transaction`** — approving a financial audit never persisted at all |
| `ProductAuditService::cancelAudit`, `FinancialAuditService::cancelAudit` | the row lands, the response 500s |
| `ExpenseCategoryController` update/delete | the row lands, the response 500s |
| `EmployeeRemunerationController` update/delete | the row lands, the response 500s |

So product audit and financial audit **approval was entirely broken**. Neither appears
in bugs.md.

The log stays inside the transaction in the two approve methods: a trail recording an
approval that then failed to commit is worse than no trail.

The `log_name` values are left exactly as each author wrote them (`updated_expense`,
`approved_expense`, ...). The working calls use English sentences (`Recorded Expense`)
and the two conventions already disagree; unifying them is a product decision that
belongs to item 9, which is specifically about deciding what counts as an important log.

### Files

- `ui/.../product-audits/CreateProductAudit.tsx`, `EditProductAudit.tsx`
- `ui/.../product-audits/CreateProductAudit.test.tsx` (2 tests, both fail on the old code)
- `ui/src/test/setup.ts` — jsdom implements neither Pointer Events capture nor
  `scrollIntoView`, and Radix's Select calls all of them the moment its trigger is
  clicked, so no test could assert anything about what a Select offers. Polyfilled
  there rather than in one test file, because every future Select test needs it.
- `api/app/Http/Controllers/ExpenseController.php`
- `api/app/Http/Controllers/ExpenseCategoryController.php`
- `api/app/Http/Controllers/EmployeeRemunerationController.php`
- `api/app/Services/ProductAuditService.php`
- `api/app/Services/FinancialAuditService.php`
- `api/tests/Feature/Audit/ActivityLoggingOnMutationTest.php` (13 tests)

Each test asserts the record survived **and** the request answered 200. The 200 is the
half that was broken; asserting only the row would have passed throughout. Eleven of the
thirteen fail on the pre-change code — the two that pass are the `store()` paths, which
were never affected.

### Verification

- Backend **801 passed** (2357 assertions), full suite.
- Frontend **774 passed, 0 failed**, full suite, and `npm run build` clean.
  The `todosRoutes.test.tsx` failure reported at the end of chunk 1 did not reproduce
  and the suite is now entirely green; it was never diagnosed, so treat it as flaky
  rather than fixed.
- `eslint` clean on every file this chunk touched.

## Chunk 3: items 5 and 6 — done

### Item 5: the audit pages could not name a person

Eager loading `performedBy()` serialises into `performed_by`, which is also the
foreign-key column, so the loaded user replaces the raw id. A User has no `name` column —
a name is `firstname` and `lastname` — so `audit.performed_by?.name` was always undefined
and the page rendered a dash for whoever actually did the audit. Same for `approved_by`.

Fixed on the financial audit detail page, the product audit detail page and the product
audit table, all of which read the same keys the same wrong way.

`personName()` now lives in `ui/src/app/utils/userName.ts` rather than being duplicated per
component. The finance transaction module already had a copy; it now imports the shared one
instead, because two spellings of "what is this person called" is how the second one drifts.

The dash fallback stays. An audit that has not been approved has no approver, and that is a
fact about the record rather than a missing name.

### Item 6: the analytics sixth card

`ProductService::analytics()` built a `topProducts` list ranking products by realised profit
with `SUM(quantity * (selling_price - cost_price))` evaluated over `sale_items`. That table
has neither column — they live on `products` — so Postgres raised 42703,
`inventoryAnalytics()` caught it and answered 500, and the card showed its error state. The
same query backed the Operations analytics page.

**Removed rather than repaired**, for two reasons:

- No consumer read it. The two components that call this endpoint read `statusBreakdown`,
  `lowStock`, `outOfStock` and the totals. Every top-products component in the app reads a
  `top_products` key from a *different* endpoint.
- It cannot be repaired as written. `sale_items` stores no cost, so a per-sale profit can
  only be derived from the product's *current* `cost_price`, which is not the cost the sale
  was made at. A correct answer needs a cost-at-sale column — a schema decision, not a bug
  fix, and rules.md says not to invent business rules.

Removing it exposed a **second, independent fault** in the same method, which had been
masked behind the first 500:

```php
->addSelect(DB::raw('((selling_price - cost_price) / cost_price) * 100 as markup_percentage'))
->having('markup_percentage', '<=', 20)
->orderBy('markup_percentage')
```

PostgreSQL accepts a select-list alias in `ORDER BY` but not in `HAVING` — `HAVING` is
evaluated before the list is projected — so this was a 42703. Rewriting it as `HAVING`
without `GROUP BY` then became a 42803 grouping error. It is a row filter, so it belongs in
`WHERE`, with the expression repeated rather than aliased.

### Files

- `api/app/Services/ProductService.php`
- `api/tests/Feature/InventoryAnalyticsTest.php` (4 tests, all fail on the old code)
- `ui/src/app/utils/userName.ts`
- `ui/src/app/pages/dashboards/executive/components/finance/financeTransaction.ts`
- `ui/src/app/pages/dashboards/executive/components/financial-audits/FinancialAuditDetail.tsx`
- `ui/src/app/pages/dashboards/executive/components/financial-audits/FinancialAuditDetail.test.tsx` (4 tests, 3 fail on the old code)
- `ui/src/app/pages/dashboards/executive/components/product-audits/ProductAuditDetail.tsx`
- `ui/src/app/pages/dashboards/executive/components/product-audits/ProductAuditTable.tsx`

### Verification

- Backend **805 passed** (2375 assertions), full suite.
- Frontend **780 passed, 0 failed**, full suite, `npm run build` clean.
- `eslint` clean on every file this chunk touched; the two remaining errors in those
  directories are pre-existing `setState in effect` warnings in the two edit dialogs.

### Noted, not fixed

`ExecutiveAnalyticsPage` and `OperationsAnalyticsPage` read `analytics.data.total_products`,
which this endpoint never returned — it returns `lowStock`, `outOfStock` and the totals.
Those pages are therefore showing zeros for a figure they were handed no key for. It is not
in bugs.md and it is not clear whether they are meant to read this endpoint or another, so it
is left for a decision rather than a guess.

## Chunk 4 plan: items 7 and 8

Both LOW and both one-line-ish, in unrelated places.

### Item 7: the products table has a delete button it should not

bugs.md: the delete button should not appear in the Actions column of the products table on
the executive dashboard; it belongs on the single product's page. Purely a column removal in
`ProductTable.tsx`.

Worth checking while there: `ProductController::destroy()` is gated on
`canDelete()` / `canEditCatalog()`, so removing the button does not remove the ability — it
removes the invitation. Confirm the single-product page has its own delete control before
taking the one away.

### Item 8: every export 404s

`ui/src/app/components/ExportButton.tsx:36` builds
`` `${import.meta.env.VITE_BASE_URL}/api/exports/${type}` ``, and `VITE_BASE_URL` already ends
in `/api`. The request goes to `/api/api/exports/products`, matches no route, and
`!response.ok` throws "Export failed". Every other RTK slice in the app uses
`${VITE_BASE_URL}/<resource>`.

Two more things sit behind that 404 and will surface the moment it is fixed:

- `api/routes/api.php:226` declares the export route inline with `middleware('role')` and no
  `auth:sanctum`. Sanctum's guard falls back to the configured `sanctum.guard` (default
  `web`), so `Auth::user()` is null, `EffectiveBranchScope::branchesFor(null)` returns null,
  the branch filter is silently skipped, and `ExportService` then calls
  `Auth::user()->business_id` on null — a 500 for customers and suppliers, and a cross-tenant
  data leak for products, sales and purchases. The route needs `auth:sanctum`.
- `ExportButton.tsx:48` hardcodes `a.download = \`${type}-...csv\`` while bugs.md asks for
  xlsx.

The xlsx half is **not** in this chunk. There is no `maatwebsite/excel` anywhere — no
composer entry, no vendor dir, no `phpoffice/phpspreadsheet` — and the implementation is a
hand-rolled CSV stream. Switching means a new dependency, `zip` and `xml` PHP extensions in
both Dockerfiles, and a rewrite of `ExportService`. That is a dependency decision, so it
gets its own chunk and its own sign-off.

### Out of scope

- The xlsx conversion, per above.
- The `total_products` / `top_products` key mismatches noted at the end of chunk 3.
- The `log_name` taxonomy, which is item 9.

