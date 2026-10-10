# Notes found along the way

Things the chunks turned up that are **not** fixed, plus decisions taken that are worth
remembering. Each entry says why it was left alone, so the next person does not have to
rediscover the reasoning.

---

## Chunk 5 completion — activity logs

### The first commit only removed the `auth` noise

Commit `3751ff4` replaced the hardcoded `log_name != 'auth'` filter with `whereNotIn['auth']` and removed `auth` from the UI category labels. That addressed the "Not x logged in" complaint but not the importance rule — Spatie's automatic `Default` logs and one-off free-text categories were still treated as important.

### The `log_name` taxonomy was normalised to sentences

The user chose "Normalise to sentences". All snake_case log_names converted:
- `auth` → `Authentication`
- `permission` → `Permission`
- `settings` → `Settings`
- `data_export` → `Data Export`
- `customer` → `Customer`
- `default` → `Default`

### The importance rule excludes `Authentication` and `Default`

`ActivityLogController` now has a `NOISE_LOG_NAMES = ['Authentication', 'Default']` constant. When the UI sends `log_name=business` (the "All business" sentinel), noise logs are excluded. When the UI sends a specific category, it is shown as requested — the user is filtering. The `categories()` endpoint also excludes noise so the filter dropdown does not offer "Authentication" or "Default".

### The auth log path is effectively dead

Login is Sanctum-token based (`UserService` calls `createToken`, never `Auth::login`), so the `Login`/`Logout`/`Failed` events that `AuthObserver` listens for never fire. The `Authentication` category is kept for completeness but is unlikely to appear in production.

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

## Chunk 8 — item 13: branch scope validation

### `branchesFor()` is load-bearing for query isolation

`EffectiveBranchScope::branchesFor()` is consumed twice: by `apply()` (the global query
scope on every `BaseModel`) and by the `$branchWithinSet` closure in ~24 form requests.
Loosening it for elevated roles fixed the form request but let elevated users read every
branch's rows, and 18 isolation tests failed. The two jobs were separated:
`validationBranchesFor()` is the relaxed set for validation; `branchesFor()` still drives
reads.

### `isExecutive()` does not exist

First attempt called `RolePermissions::isExecutive($user)`; PHP threw an `Error` for the
undefined method. The real method is `isElevated()` (`executive`, `coresupport`, `siteadmin`).
The 35 failures on that attempt were the typo plus the genuine 18.

### Two isolation tests used the wrong actor

`TenantIsolationTest::test_branch_user_cannot_create_product_in_a_different_branch` and
`TaxPaymentTest::test_payment_tax_category_must_belong_to_own_branch` both created an
**Executive** and asserted cross-branch rejection — the exact behaviour the bug report says
is wrong. Rewritten to use a branch-scoped `BranchManager`, which is the role the isolation
rule protects.

### The cache directory was re-committed almost every push

`api/storage/framework/cache/` had no `.gitignore`, so `git add -A` kept staging framework
cache files. Added Laravel's default `cache/.gitignore` (`*`, `!data/`, `!.gitignore`,
`!.gitignore`) and `cache/data/.gitignore` (`*`, `!.gitignore`).

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

---

## Chunk 5 completion — activity logs

### The first commit only removed the `auth` noise

Commit `3751ff4` replaced the hardcoded `log_name != 'auth'` filter with `whereNotIn['auth']` and removed `auth` from the UI category labels. That addressed the "Not x logged in" complaint but not the importance rule — Spatie's automatic `Default` logs and one-off free-text categories were still treated as important.

### The `log_name` taxonomy was normalised to sentences

The user chose "Normalise to sentences". All snake_case log_names converted:
- `auth` → `Authentication`
- `permission` → `Permission`
- `settings` → `Settings`
- `data_export` → `Data Export`
- `customer` → `Customer`
- `default` → `Default`

### The importance rule excludes `Authentication` and `Default`

`ActivityLogController` now has a `NOISE_LOG_NAMES = ['Authentication', 'Default']` constant. When the UI sends `log_name=business` (the "All business" sentinel), noise logs are excluded. When the UI sends a specific category, it is shown as requested — the user is filtering. The `categories()` endpoint also excludes noise so the filter dropdown does not offer "Authentication" or "Default".

### The auth log path is effectively dead

Login is Sanctum-token based (`UserService` calls `createToken`, never `Auth::login`), so the `Login`/`Logout`/`Failed` events that `AuthObserver` listens for never fire. The `Authentication` category is kept for completeness but is unlikely to appear in production.

---

## Chunk 9 — item 14: suppliers/customers (parity only)

### The bug premise was stale

The pages, routes, sidebar entries, tables, dialogs and exports for Suppliers and Customers
already existed (`ExecutiveRoutes.tsx:97,114`, `ExecutiveSuppliersPage.tsx`,
`ExecutiveCustomersPage.tsx`). The bug was reported before that frontend work landed.

### Why the executive "saw no suppliers/customers"

`CoreBusinessSettings::coreSettings()` seeded `SuppliersSettings` and `CustomersSettings`
with `status => 'disabled'`, and `useFeatureSettings()` hides the People sidebar items whose
`settingKey` is disabled. So a new business showed neither entry until the executive toggled
them under Settings. Both now default to `enabled`; other core settings stay disabled.

### Branch-manager customer detail was the one real route gap

`BranchManagerRoutes.tsx` had `suppliers/:id` but only `customers` — no detail. Added
`customers/:id`, reusing the executive `Customer` component.

### The supplier write boundary is intentional and was kept

`SupplierPermissionsTest` (13 tests) and `RolePermissions::canManageSuppliers()` document
that a supplier is business-level (one `supplier_code`, `business_branch_id` null), so
BranchManager may read but not author. The bug's "branch manager manages suppliers" was not
implemented, per user decision.

### Latent item-12 type error

`BranchDetail.tsx` passed `useParams()`'s `string | undefined` into `useBranchQuery(id)`;
`npm run build` (not run at item 12) caught it. Fixed with `id ?? ''`.

---

## Migration normalisation — one salaries table, one Salary model

### There were two migrations creating `salaries`, and the wrong one had already run

`2026_10_09_175742_create_salaries_table` created `salaries` with nothing but `id`,
`timestamps` — a stub. `2026_10_09_200000_create_salaries_table` has the real shape.

The stub was committed *and migrated* (dev DB row, batch 1), so the real migration
could never run: it is `Schema::create('salaries')` against a table that already
existed. `migrate:status` showed 200000 "Pending" forever, and `salaries` stayed
2 columns wide. Deleting the stub alone is not enough — the table and the
`migrations` row have to be reconciled too, which is what this chunk did.

Deleted, with a guard that refuses to run if either table has rows:

- `2026_10_09_175742_create_salaries_table.php` (the stub)
- `2026_06_19_202844_create_employee_salaries_table.php` — the superseded
  per-worker `employee_salaries` table. `bugs.md` item 20 replaces it, and
  nothing read it after the rename.
- `2026_01_01_000010_create_purchase_items_table.php.bak` — a stray backup
  committed in `621552b`. It is a full migration file, so Laravel would have
  tried to load it as a second `purchase_items` creation.

The other duplicate timestamps in the directory (`2026_01_01_000003`,
`2026_05_21_202854`, `2026_09_26_000001`, …) are **not** duplicates — they are
different tables that happen to share a prefix. `grep` on `Schema::create` finds
`salaries` as the only table created twice. Left alone deliberately.

### The `Schema::table` index block was not dead weight

`bugs.md` item 20's `salaries` migration closed with a second
`Schema::table('salaries', ...)` adding two indexes, and that reads as noise next
to a single `Schema::create`. It was removed as a *statement* — the indexes moved
into the `Schema::create` closure — but they are kept, because
`foreignId()->constrained()` creates the FK constraint and **not** an index in
Postgres, and `BaseModel` applies a `business_id` scope plus
`EffectiveBranchScope` filters `business_branch_id` on every read of this table.
Dropping them would be a silent full scan on the payroll query.

### `booted()` in a `BaseModel` subclass silently drops the tenant scopes

`Salary` overrides `booted()` to re-assert a null `business_branch_id`. Laravel
calls `static::booted()` **once**, and it resolves to the most-derived
definition — so the parent's `booted()` never runs, and with it both global
scopes and the `business_id` stamp. The symptom is invisible in a single-tenant
test: the model reads across tenants, and `business_id` lands NULL against a
NOT NULL column. Caught by the cross-tenant test in `SalaryTest`, which saw
another business's row. `parent::booted()` is required in every subclass that
declares its own. `ActivityLog` is unaffected — it extends Spatie's `Activity`,
not `BaseModel`.

### An explicit `business_branch_id: null` was being overwritten

`BaseModel`'s `creating` hook stamps the caller's branch when the attribute is
not set, and it tests with `isset()` — which is **also** false for an explicit
null. So passing `null` to mean "all branches" is indistinguishable from omitting
it, and the creator's branch wins.

`EffectiveBranchScope`'s own comment documents a NULL `business_branch_id` as the
business-level value, so that representation is the intended one and was simply
unreachable from any request. Fixed locally on `Salary` with a
`$coversAllBranches` flag, because changing the hook would change what "unset"
means for every model in the app. `update()` ignores a null too, so the
controller `forceFill`s it.

### The `enum:active,inactive` cast does not exist in Laravel 13

Both the old `EmployeeSalary` and the new `Salary` carried
`'status' => 'enum:active,inactive'`. That is not a supported cast in Laravel 13 —
it raised `InvalidCastException` on **every read** of the model, and it is the
reason the old controller was unusable rather than merely mismatched. It was
never exercised because no test touched the model. Now a plain string cast; the
DB check constraint and the form requests hold the value to active/inactive.

### `BranchDetailSummaryTest` was already broken at HEAD

Not caused by this chunk, and it was **masked** by the duplicate migration. Commit
`621552b` removed `business_id`/`business_branch_id` from `Worker::$fillable`
(bugs.md item 17), so `Worker::create([... 'business_branch_id' => $branch])` in
the test silently drops both, while two readers still queried the now-never-written
column:

- `BusinessBranchController::show()` counted workers by that column, so the
  branch page's worker card read 0.
- `AttendanceController::store()` built its branch map from it, so the map was
  always empty and every worker in an attendance batch fell through to the
  *caller's* branch.

Both now go through `user.business_branch_id` — the same source
`Worker::getBusinessBranchAttribute()` already used. Worth remembering: dropping a
column from `$fillable` does not stop readers querying it, and Eloquent does not
raise on a mass-assigned unknown key.

### `SalarySeeder` cannot read `Role` through the model

`Role` carries `BaseModel`'s tenant scopes and a seeder has no authenticated user,
so `Role::query()` resolves to `whereRaw('0 = 1')` and finds nothing. The first
attempt failed with `Call to undefined relationship [business]` on `Role` (there
is no `business()` relation on it), and the second with a NOT NULL violation on
`business_id`, because with no session there is no context to stamp it from. It
reads `roles` through the query builder and sets `business_id` explicitly. The
guard is `whereNotNull('business_id')` on roles, which is also how a platform role
with no business is skipped.

### Verification

- `docker compose exec -T backend php artisan test` → **833 passed** (2468 assertions)
- `pint --test` on all 14 touched PHP files → clean
- `npx tsc -b` → clean
- `npx vitest run` → **803 passed** across 18 files
- `npx vite build` → built
- `todosRoutes.test.tsx` failed once in a full run and passed alone and on re-run.
  Consistent with the flakiness already recorded in chunk 1; not diagnosed.

## Chunk 10 — items 18 & 19: expense analytics mirroring, live currency rates

### Item 18: expenses were missing from analytics because cash_flows desynced

Analytics read only `cash_flows`. `ExpenseController::store()` mirrored the expense
via `CashFlowService::createCashFlowForExpense()`, but `update()` and `destroy()` did
not, and `ExpenseSeeder` created expenses with no mirror at all. Fixed: `update()` now
refreshes the mirrored row amount/date, `destroy()` deletes it, and the seeder creates
a `CF-EXP-*` mirror per seeded expense when absent.

### Item 19: currency rates had no live source

`syncCurrencyRates()` was a no-op stub with no route, and no artisan command, schedule
or provider config existed. Added `config/currency.php`, `CurrencyRateService` (fetch,
per-business upsert, delete-then-insert refresh so reruns never duplicate), the
`duukaflow:currency:sync-rates` command, a `POST /sync` route, and a 06:00 daily
schedule in `routes/console.php`. `currency_rates.business_id` is NOT NULL, so rates
are written per business, grouped by base currency to keep one provider call per
currency; `withoutGlobalScopes()` + explicit `business_id` is used because a command
has no authenticated user to stamp from. New `CurrencyRateSyncTest` covers sync,
no-duplication, provider failure, and forced base.

### Verification

- `php artisan test` → **845 passed** (2492 assertions)
- New: `CurrencyRateSyncTest` — 4 passed


## Chunk 11 — item 20: EmployeeSalary -> Salary (verification + tracking)

The rename was already implemented in 0a8aeac during the salaries migration
normalisation, ahead of the ranked order. This chunk is the verification pass:

- Model `Salary`, controller, `Store/UpdateSalaryRequest`, `SalaryPolicy`, factory,
  seeder, and the `salaries` migration (id, business_id, nullable
  business_branch_id, role_id, amount, period, status enum, set_by, soft deletes)
  all exist and speak the role-driven contract; `EmployeeSalary*` is gone apart from
  explanatory comments. Route is `/dashboard/salaries`, frontend uses `salaryQuery`
  + `ExecutiveSalariesPage`/`SalaryForm`/`SalaryPanel`, sidebar splits Payroll and
  Salaries.

- `SalaryTest` (15 tests) pins: role-keying, monthly_payroll excluding yearly/
  inactive, all-branches null business_branch_id, branch pinning, cross-tenant role/
  branch refusal on create, soft delete, 404 on unknown id, and 401 unauthenticated.

Verification: backend 845 passed (2492 assertions); frontend tsc clean, 805 passed
(18 files). No code changes needed — row 20 marked **done**.
