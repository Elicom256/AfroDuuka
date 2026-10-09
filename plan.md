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
| 7 | Products table: delete button in wrong place | Cosmetic column in the executive products table | LOW | **done** |
| 8 | Export failure (all exports 404) | `VITE_BASE_URL` already ends in `/api`; `ExportButton` appends a second. Route also lacks `auth:sanctum` | LOW for the 404, MED for xlsx | **done** (404 + auth; xlsx deferred) |
| 9 | Activity logs: noise + employee scoping | Dashboard widget passes no `log_name`; no importance rule | LOW-MED | **done** |
| 10 | Stock transfer dispatch unique violation | `resolveDestinationProduct()` searches through the branch global scope, cannot see the destination row, blind-inserts | LOW-MED | **done** |
| 11 | Workers: duplicate employee_code | Code generated from a tenant-scoped `count()` against a global unique index; seeded rows have `business_id = NULL` so the count is permanently 0 | LOW-MED | **done** |
| 12 | Branches: no single-branch page | No `dashboard/branches/:id` route or summary page | MED | **done** |
| 13 | Workers: cannot add to another branch | `EffectiveBranchScope::branchesFor()` grants all-branches only when `business_branch_id IS NULL`, and onboarding always pins the executive to one branch. Affects 39 form requests | MED | **done** |
| 14 | Suppliers + Customers pages | Backend exists, no frontend routes under People | MED | **done** |
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

## Chunk 4: items 7 and 8 — done

### Item 7: the products table delete button

Removed from the Actions column. The delete now lives only on the single product's page,
which already had its own control with the product's name in the confirmation.

Removing the button took the whole supporting path with it: `useDeleteProductMutation`,
`useRolePermissions`, `ConfirmDeleteButton`, the `Trash2` icon, the `toast` import and
`handleDelete`. All of them existed only for that one button.

`ProductController::destroy()` is unchanged and still gated on `canDelete()`, so the
ability is not removed — only the invitation from a dense, repeated row control.

The test had to be written carefully because two things make the button invisible to a
naive assertion. It is gated on `canDelete`, which comes from the signed-in user's role, so
a test that signs nobody in sees no button whether it was removed or merely hidden. And it
is icon-only with no accessible name, so `getByRole('button', { name: /delete/i })` never
matched it. The test mocks `useLoggedinUserQuery` with an elevated role and selects on the
lucide icon class. It also asserts the edit control survived and that the row still
navigates to the product, since "remove the button" done carelessly takes the actions
column or the navigation with it.

### Item 8: every export was 404ing

`ExportButton.tsx` built `` `${VITE_BASE_URL}/api/exports/${type}` `` while `VITE_BASE_URL`
already ends in `/api`, so the request went to `/api/api/exports/{type}`, matched no route,
and answered 404. Every other RTK slice in the app builds `${VITE_BASE_URL}/<resource>`.

The route also declared `middleware('role')` with no `auth:sanctum`. Sanctum's guard falls
back to the configured `sanctum.guard` (default `web`), so `Auth::user()` was null,
`EffectiveBranchScope::branchesFor(null)` returned null, the branch filter was silently
skipped, and `ExportService` then called `Auth::user()->business_id` on null. That is a 500
for customers and suppliers, and a cross-tenant data leak for products, sales and purchases.

Fixing those two exposed a **third fault**: `ExportService` read `name`, `phone`, `email`
and `location` off `Customer` and `Supplier`, and neither table has those columns — only
`company_name` and a `user_id`. Reading `->name` off a loaded Eloquent model returns null
rather than raising, so the export produced a file with blank columns and looked like it had
worked. The sales and purchases exports had the same fault one level up. Fixed by exporting
the columns that exist and eager-loading `user` for the contact details.

A fourth, smaller one: an unknown export type threw a plain `InvalidArgumentException`,
which surfaces as a 500. A bad path segment is a client error, so it is a 404 now.

### Not backend-testable

Fault 1 is in the frontend URL, so no backend test can see it. Fault 2 is invisible under
`Sanctum::actingAs()`, which sets the user on the sanctum guard as well as the web one. The
customers and suppliers tests are what catch it. This is written into the `ExportTest`
class comment so the two products tests are not mistaken for coverage of it.

### Files

- `ui/src/app/components/ExportButton.tsx`
- `ui/src/app/pages/dashboards/executive/components/products/ProductTable.tsx`
- `ui/src/app/pages/dashboards/executive/components/products/ProductTable.test.tsx` (3 tests, 1 fails on the old code)
- `api/routes/api.php`
- `api/app/Services/ExportService.php`
- `api/tests/Feature/ExportTest.php` (6 tests, 4 fail on the old code)

### Verification

- Backend **811 passed** (2393 assertions), full suite.
- Frontend **785 passed, 0 failed**, full suite, `npm run build` clean.
- `eslint` clean on every file this chunk touched. Warnings are back at 931, the
  `--max-warnings` threshold: the chunk-3 test had pushed them to 932 with an `as any`,
  which is fixed.

## Chunk 5 plan: item 9 — activity logs

The executive's dashboard and the activity-log page show every log, including noise. Item 9
asks for two things: the executive should see only important logs unless he filters, and
non-executive employees should see only their own.

### What is already there

The scoping half is largely implemented. `ActivityLogController::index()` restricts
supervisory roles to the business, a branch manager to their branch, and everyone else to
`causedByUser($user)`. `ActivityLogPage` is routed with `scope='business'` for the executive
and `scope='personal'` for Operations and BranchManager. So "employees see only their own
logs" mostly holds, with one deviation: a branch manager sees their whole branch rather
than only its own actions.

What is missing is the importance rule. The only filter is a single hardcoded string —
`ActivityLogController` excludes exactly `'auth'`. There is no notion of importance, so
Spatie's automatic `default` logs and one-off free-text categories are all treated as
important, and `categories()` returns raw `DISTINCT log_name` values, which puts sentences
like `'Created Financial Audit'` in the filter dropdown next to `'updated_expense'`.

Also worth knowing before planning: the auth log path is effectively dead. Login is
Sanctum-token based (`UserService` calls `createToken`, never `Auth::login`), so the
`Login`/`Logout`/`Failed` events that `AuthObserver` listens for never fire. And even if
they did, `ActivityLog::booted()` stamps `business_id` from `Auth::user()`, which is not yet
populated during the `Login` event — so the row would land with a NULL tenant and be
invisible to `forBusiness()` anyway. The "x logged in" noise the report describes may
already not be appearing, which would mean the real problem is the missing importance rule
rather than the auth logs. That needs confirming before anything is built on it.

### The `log_name` decision

The two conventions already coexist: the working calls use English sentences
(`Recorded Expense`), the converted ones in chunk 2 kept the snake_case each author wrote
(`updated_expense`, `approved_expense`). Item 9 is where that gets settled.

This is a product decision, not a mechanical one, so the chunk should confirm the intended
taxonomy with you rather than pick one. Options: normalise everything to sentences,
normalise everything to snake_case, or keep both and add an explicit `is_noise` / severity
column that both map onto.

### Out of scope

- The xlsx conversion, per the chunk 4 note.
- The `total_products` / `top_products` key mismatches from chunk 3.

## Chunk 5 completion plan: item 9 — activity logs

The first commit (3751ff4) only removed the `auth` noise. Still missing:

1. **Normalise all snake_case log_names to sentences.** The user chose "Normalise to sentences".
   Files to update:
   - `AuthObserver.php`: `'auth'` → `'Authentication'`
   - `UserService.php`: `'permission'` → `'Permission'`
   - `ReportExportController.php`: `'data_export'` → `'Data Export'`
   - All `Settings/*Controller.php`: `'settings'` → `'Settings'`
   - `ActivityLogSeeder.php`: normalise all seeded log_names

2. **Add importance rule.** The executive should see only important logs by default.
   Spatie's automatic `default` logs (model events with no explicit log_name) are noise.
   Add a `NOISE_LOG_NAMES` constant to `ActivityLogController` containing `['Authentication', 'Default']`.
   When the UI sends `log_name=business` (the "All business" sentinel), exclude noise logs.
   When the UI sends a specific category, show it as requested (the user is filtering).

3. **Update `categories()` to exclude noise** so the filter dropdown doesn't show
   "Authentication" or "Default" options.

4. **Frontend**: The `ActivityLogPage` already sends `log_name=business` for the
   "All business" option. No frontend changes needed since the backend handles it.

### What is noise vs important

| log_name | Verdict | Reason |
|----------|---------|--------|
| `Authentication` | noise | Login/logout events — the report explicitly says "Not x logged in" |
| `Default` | noise | Spatie's automatic model events — no explicit category means noise |
| `Permission` | important | Explicit user action |
| `Settings` | important | Explicit user action |
| `Data Export` | important | Explicit user action |
| `Customer` | important | Explicit user action |
| `Recorded Expense` | important | Explicit user action |
| `Created Financial Audit` | important | Explicit user action |

### Files to modify

- `api/app/Observers/AuthObserver.php` — normalise log_name
- `api/app/Services/UserService.php` — normalise log_name
- `api/app/Http/Controllers/ReportExportController.php` — normalise log_name
- `api/app/Http/Controllers/Settings/*.php` — normalise log_name
- `api/database/seeders/ActivityLogSeeder.php` — normalise seeded log_names
- `api/app/Http/Controllers/ActivityLogController.php` — add importance rule + categories filter
- `api/tests/Feature/Audit/ActivityLoggingOnMutationTest.php` — update tests for new log_names
- Any other tests that assert on log_name values

## Chunk 6 plan: item 10 — stock transfer dispatch unique violation

### The bug

`StockTransferService::resolveDestinationProduct()` searches for a matching product on the
destination branch by sku → barcode → name. But `Product` extends `BaseModel`, which applies
the `EffectiveBranchScope` global scope. When an executive (pinned to branch 1) dispatches a
transfer to branch 2, the scope filters out branch 2's products, so the method can't find the
existing product and tries to create a duplicate — violating the
`products_business_branch_id_name_unique` constraint.

### The fix

`resolveDestinationProduct` must query without the branch global scope, since it explicitly
knows which branch it's looking for. Use `Product::withoutGlobalScope('branch')` when
searching for the destination product. The business scope can stay — the destination branch
belongs to the same business.

### Files to modify

- `api/app/Services/StockTransferService.php` — add `withoutGlobalScope('branch')` to the
  `resolveDestinationProduct` query
- Add a test that dispatches a transfer to a branch that already has a product with the same
  name, asserting no unique violation

## Chunk 6/7 results: items 10, 11, 12 — done

- **Item 10** (`3311a65`): `resolveDestinationProduct()` now queries
  `Product::withoutGlobalScope('branch')->where('business_branch_id', $toBranchId)` so it sees
  the destination row instead of blind-inserting.
- **Item 11** (`8a6718b`): `WorkerService::addWorker()` generates `employee_code` inside the
  transaction from `Worker::withoutGlobalScopes()->lockForUpdate()->max('employee_code') + 1`,
  so the global unique index is respected even when seeded rows have `business_id = NULL`.
- **Item 12** (`b0f0869`): added lazy `dashboard/branches/:id` route in `ExecutiveRoutes.tsx`,
  new `BranchDetail.tsx` (workers/products/income/expense cards + edit/delete), and made the
  branch cards in `BusinessBranches.tsx` link to it.

## Chunk 8: item 13 — cannot add workers to another branch

### The bug

All ~24 `*Request` classes validate `business_branch_id` with a `$branchWithinSet` closure
that resolves `EffectiveBranchScope::branchesFor($user)` and rejects any id outside
`[null, [$user->business_branch_id]]`. Onboarding pins an executive to branch 1, so an
executive can only ever post to branch 1 — "The server rejected that request. Check the
values and try again." on every other branch.

### Why not fix `branchesFor()`

The first attempt added the elevated check directly to `branchesFor()`. That method also
drives `EffectiveBranchScope::apply()`, the global query scope on every `BaseModel`, so
elevated roles suddenly saw every branch's rows in queries and **18 isolation tests failed**
(35 before the non-existent `RolePermissions::isExecutive()` typo was corrected to
`isElevated()`). The global scope is intentionally unchanged.

### The fix (`ee83d4e`)

Added `EffectiveBranchScope::validationBranchesFor(?User)`, which returns
`branchesFor()` for everyone except elevated roles, and every branch id of the user's
business for `RolePermissions::isElevated()` users (`executive`, `coresupport`, `siteadmin`).
All 24 form-request closures now call `validationBranchesFor()`. The query scope still calls
`branchesFor()`, so branch isolation in reads is untouched.

Two tests asserted the old behaviour with an Executive actor and were rewritten to use a
`BranchManager` (non-elevated), which is who the isolation rule actually protects:

- `TenantIsolationTest::test_branch_user_cannot_create_product_in_a_different_branch`
- `TaxPaymentTest::test_payment_tax_category_must_belong_to_own_branch`

### Files

- `api/app/Support/Tenant/EffectiveBranchScope.php`
- `api/app/Http/Requests/*.php` (24 request classes)
- `api/tests/Feature/TenantIsolationTest.php`
- `api/tests/Feature/Tax/TaxPaymentTest.php`
- `api/storage/framework/cache/.gitignore`, `.../data/.gitignore` — Laravel's default cache
  ignores, which were missing; the cache directory had been re-committed on nearly every
  push. This ends it.

### Verification

- Backend **811 passed** (2393 assertions), full suite.

## Chunk 9 plan: item 14 — Suppliers + Customers pages

Backend for both already exists. Add frontend routes/pages under the **People** section and
let executive and branch manager manage them. Next up.

## Chunk 9 result: item 14 — done (`c793f85`), parity-only

The premise of the bug was stale: the Suppliers and Customers pages, routes, sidebar entries,
tables, form dialogs and export buttons **already existed**. The two real gaps were:

1. **New businesses seeded both features `disabled`.** `CoreBusinessSettings::coreSettings()`
   created `SuppliersSettings` and `CustomersSettings` with `status => 'disabled'`, and the
   sidebar gates each People item on `useFeatureSettings()[settingKey]`. So an executive saw
   no Suppliers/Customers until they found the Settings toggles. Both now default to
   `enabled`; every other core setting stays opt-in.
2. **BranchManager had no `customers/:id` route.** `BranchManagerRoutes.tsx` had
   `suppliers/:id` but only the customer list, so a branch manager could not open a customer.
   Added the lazy `Customer` import and the `customers/:id` route, matching suppliers.

The branch-manager supplier **write** boundary is deliberately left alone. `SupplierPolicy`
+ `RolePermissions::canManageSuppliers()` (elevated only) and `SupplierPermissionsTest`
encode that a supplier is business-level with one `supplier_code` per business, so a branch
manager may read the list (purchases need a supplier name) but not fork it per branch. The
bug's "branch manager manages suppliers" wording conflicts with that rule, and the user chose
to keep it.

Also fixed a latent item-12 type error: `BranchDetail.tsx` passed `useParams()`'s
`string | undefined` id straight into `useBranchQuery(id)`, which only surfaced when
`npm run build` was run for this chunk.

### Files

- `api/app/Services/CoreBusinessSettings.php`
- `api/tests/Feature/CoreBusinessSettingsTest.php` (3 tests)
- `ui/src/app/routes/BranchManagerRoutes.tsx`
- `ui/src/app/pages/dashboards/executive/pages/BranchDetail.tsx`

### Verification

- Backend **814 passed** (2398 assertions), full suite.
- Frontend **787 passed**, full suite, `npm run build` clean, eslint clean on touched files.

## Chunk 10 plan: item 15 — product update on purchase

Product mutation lives on `/receive`, which the UI never calls; `selling_price` is not
editable at purchase time. Next up.


