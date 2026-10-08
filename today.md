# Today

## Ranking of bugs.md items (easiest -> hardest)

Ties broken by blast radius: cheap-and-unblocks-others ranks above cheap-and-isolated.

| # | Bug | Root cause | Complexity |
|---|---|---|---|
| 1 | **Transactions page not accessible** | `RolePermissions` missing import in `FinanceController` -> HTTP 500. Also `finance/transactions/:id` route genuinely absent | LOW |
| 2 | **Reports fail to load** | Same missing import, same file | LOW |
| 3 | **Product Audit: no products selectable** | Dialogs read `products.data`; `ProductController::index()` returns a plain array under `products` | LOW |
| 4 | **Approve expense: writes but toast fails** | `ActivityLog::log()` does not exist; throws after the un-transacted write | LOW (MED across all 11 sites) |
| 5 | **Financial audit "performed by" empty** | Eager-loaded relation serialises as `performedBy`, UI reads `performed_by` | LOW |
| 6 | **Analytics: 6th card fails** | `withSum` subquery references `selling_price`/`cost_price` on `sale_items`, which has neither | LOW-MED |
| 7 | **Products table: delete button in wrong place** | Cosmetic column in the executive products table | LOW |
| 8 | **Export failure (all exports 404)** | `VITE_BASE_URL` already ends in `/api`; `ExportButton` appends a second one. Route also lacks `auth:sanctum` | LOW for the 404, MED for real xlsx |
| 9 | **Activity logs: noise + employee scoping** | Dashboard widget passes no `log_name`; no importance rule for the rest | LOW-MED |
| 10 | **Stock transfer dispatch unique violation** | `resolveDestinationProduct()` searches through the branch global scope, cannot see the destination row, blind-inserts | LOW-MED |
| 11 | **Workers: duplicate employee_code** | Code generated from a tenant-scoped `count()` against a global unique index; seeded rows have `business_id = NULL` so the count is permanently 0 | LOW-MED |
| 12 | **Branches: no single-branch page** | No `dashboard/branches/:id` route or summary page | MED |
| 13 | **Workers: cannot add to another branch** | `EffectiveBranchScope::branchesFor()` grants all-branches only when `business_branch_id IS NULL`, and onboarding always pins the executive to one branch. Affects 39 form requests | MED |
| 14 | **Suppliers + Customers pages** | Backend exists, no frontend routes under People | MED |
| 15 | **Product update on purchase** | Product mutation lives on `/receive`, which the UI never calls; `selling_price` is not editable at purchase time | MED |
| 16 | **Receipt redesign (business name, logo, QR)** | Template and React component never read business identity; no `business()` relation; no QR dependency; dompdf `enable_remote=false` | MED |
| 17 | **Workers: drop `business_id`/`business_branch_id`** | Duplicated from `users`, actor-stamped by `BaseModel`, read by `AttendanceController` | MED |
| 18 | **Expenses missing from analytics** | Analytics read `cash_flows` only; `expenses` rows are mirrored only via `ExpenseController::store()`, so seeders/updates/deletes desync | HIGH |
| 19 | **Currency rates from a live source** | Fully manual today; needs provider config, an artisan sync command, and a schedule | HIGH |
| 20 | **EmployeeSalary -> Salary rename** | Model, controller, requests, migrations, seeders, routes, frontend all renamed; role now drives salary | HIGH |
| -- | Subscriptions | Explicitly deferred in bugs.md | SKIP |

---

## Chunk 1 plan: Transactions + Reports

Items 1 and 2 are one bug. Both `GET /api/finances/transactions` and all three
`GET /api/finances/reports/*` endpoints die on the same line.

### Root cause

`api/app/Http/Controllers/FinanceController.php:57` calls
`RolePermissions::canManageSensitiveFinance(Auth::user())`, but
`App\Support\Auth\RolePermissions` is not imported. Inside
`namespace App\Http\Controllers` PHP resolves it to
`App\Http\Controllers\RolePermissions`, which does not exist. That raises an
`Error`, not an `\Exception`, so the surrounding `catch (\Exception $e)` in all
seven guarded methods does not catch it and the request 500s.

`authorizeSensitiveFinance()` is reached from lines 85, 135, 186, 210, 233, 255
and 274. Nothing in the test suite hits those GETs -- `FinanceCashBalanceTest`
only exercises `POST /api/finances/adjustments`, which authorizes correctly in
`StoreCashFlowAdjustmentRequest::authorize()`.

Git confirms a regression: `cc32459` removed the import, `2cbd79a` added the
call site.

### Scope of the change

Backend

1. `api/app/Http/Controllers/FinanceController.php` -- add
   `use App\Support\Auth\RolePermissions;`. This is the actual fix.
2. Same file -- hoist the gate above the `try` in the seven guarded methods.
   `resolveBranchId()`'s `abort(403)` and `authorizeSensitiveFinance()`'s
   `abort_unless(403)` sit inside the `try`, so once the import is fixed a
   legitimate 403 is still swallowed into `422 "Failed to fetch ..."`.
   `adjustment()` already documents this pattern working
   (`FinanceController.php:153-154`), so this is a consistency fix, not a new
   rule.
3. Same file -- `transaction()` loads the row inside the `try`, so
   `findOrFail` is caught and an unknown id answers 422 instead of 404. Move
   the lookup above the `try` and let `ModelNotFoundException` surface.
4. Same file -- `transaction()` eager-loads only `branch, createdBy, customer,
   supplier, sale, purchase`. The detail page shows every source document, so
   add `expense, stockTransfer, saleReturn, purchaseReturn`.

Frontend

5. `ui/src/app/routes/ExecutiveRoutes.tsx` and
   `ui/src/app/routes/BranchManagerRoutes.tsx` -- add
   `finance/transactions/:id`. `FinanceTransactionTable.tsx:201` already links
   to it and currently lands on NotFound.
6. New page `ExecutiveFinanceTransactionDetailPage.tsx`, modelled on
   `ExecutiveFinancialAuditPage.tsx`: back arrow to
   `/dashboard/finance/transactions`, title with the transaction code,
   `PageLoadingState` while loading, not-found fallback.
7. New component `FinanceTransactionDetail.tsx` showing, as cards:
   - Amount, signed by the same `isOutflow` rule the table uses, plus the
     direction repair control for a signless adjustment
   - Transaction metadata: date, category, payment method, reference, currency,
     status
   - People and place: branch, recorded by, customer, supplier
   - Source document: sale, purchase, sale return, purchase return, stock
     transfer, expense -- whichever is present, with a link where one exists
   - Notes
8. Extract the pieces the detail page shares with the table --
   `isOutflow`, `typeColors`, `typeVariant` and the direction control -- into
   `financeTransaction.tsx` so both import one copy. Sign logic that exists
   twice is how the table's existing invariant gets silently broken.

`GET /api/finances/transactions/{id}` already exists at
`api/routes/finances.php:17` and `useGetFinanceTransactionQuery` already exists
at `ui/src/app/store/features/finance/financeQuery.ts:26`, so no new endpoint
or slice.

### Tests to add

`api/tests/Feature/Finance/FinanceTransactionsAndReportsTest.php` -- smoke
coverage for the endpoints that were silently 500ing:
- `GET /api/finances/transactions` returns 200 for an executive
- `GET /api/finances/transactions/{id}` returns 200 with the source document
  eager-loaded
- `GET /api/finances/reports/revenue` returns 200 for an executive
- `GET /api/finances/reports/expenses` returns 200
- `GET /api/finances/reports/income-summary` returns 200
- `GET /api/finances/transactions` returns 403, not 422, for a role that
  `canManageSensitiveFinance()` refuses
- `GET /api/finances/transactions/{id}` returns 404 for an unknown id

`ui/src/app/pages/dashboards/executive/components/finance/FinanceTransactionDetail.test.tsx`:
- an unsigned adjustment renders neither a `+` nor a `-` and offers the
  direction control
- the source-document card only lists the relations the row actually has

### Verification

- `dcdev` alias, all tests inside Docker.
- Backend: the feature test file above.
- Frontend: `npm run test`, plus `npm run build` for the type check.
- Manual: `/dashboard/finance/transactions` and `/dashboard/finance/reports`
  load; clicking the eye on a row opens the detail instead of NotFound.

### Out of scope for this chunk

- Expenses-in-analytics desync (item 18) -- different subsystem.
- The orphaned `ExecutiveFinancesPage.tsx` -- dead file, clean up separately.
- `running_balance` on the detail page -- only `FinanceService` attaches it to a
  list, so the detail page shows no running balance rather than inventing one.

---

## Chunk 1 result: done

Two of the bugs found on the way are worth recording, because neither was in
the bug report and both are the same class of fault as the one that was.

### The fix

- `FinanceController.php` -- `use App\Support\Auth\RolePermissions;`. That one
  line took the transactions list and all three reports from 500 to 200.
- The role gate, the branch scope check and the `findOrFail` moved above the
  `try` in the seven guarded methods, so a refusal answers 403 and an unknown id
  answers 404 instead of both being flattened into 422.
- `transaction()` now eager-loads every relation a ledger row can point at,
  plus `customer.user` and `supplier.user`.

### Two extra defects found

**Asking for your own branch was refused.** `resolveBranchId()` compared the
branch id with `in_array(..., true)`. Query strings and path segments are
strings, `EffectiveBranchScope::branchesFor()` returns integers, so `'1' !== 1`
and the user got a 403 on the branch the transactions page's own dropdown had
just sent. Latent behind the 500, live the moment the import was fixed.

**Nobody can be named from a person or a customer.** `createdBy` eager-loads
into the `created_by` key, which is also the foreign-key column, so the loaded
user replaces the raw id. And `Customer`/`Supplier` have no name column at all:
a person's name is `firstname` + `lastname` on the user behind them, a company
is `company_name`. `Customer::name()` is a plain method, so it never reaches
the JSON. The detail view therefore reads those, and the backend loads
`customer.user` / `supplier.user` to make that possible.

This is the same fault as bug item 5 in the ranking -- the financial audit's
empty "performed by" -- and it is probably not the only place it happens.

### Files

- `api/app/Http/Controllers/FinanceController.php`
- `api/tests/Feature/Finance/FinanceTransactionsAndReportsTest.php` (11 tests)
- `ui/src/app/pages/dashboards/executive/components/finance/financeTransaction.ts`
- `ui/src/app/pages/dashboards/executive/components/finance/financeTransactionComponents.tsx`
- `ui/src/app/pages/dashboards/executive/components/finance/FinanceTransactionDetail.tsx`
- `ui/src/app/pages/dashboards/executive/components/finance/FinanceTransactionDetail.test.tsx` (6 tests)
- `ui/src/app/pages/dashboards/executive/components/finance/FinanceTransactionTable.tsx`
- `ui/src/app/pages/dashboards/executive/pages/ExecutiveFinanceTransactionPage.tsx`
- `ui/src/app/routes/ExecutiveRoutes.tsx`, `ui/src/app/routes/BranchManagerRoutes.tsx`

Split into `.ts` and `.tsx` because `react-refresh/only-export-components`
rejects a file that exports both components and helpers.

### Verification

- Backend: 788 passed (2313 assertions), full suite.
- Frontend: 769 passed, 1 failed -- `todosRoutes.test.tsx` fails on a clean
  checkout too, so it is pre-existing and unrelated. Needs its own look.
- `npm run build` clean, `eslint` on the touched files clean.