# Frontend RTK state — roadmap to fix stale analytics

Derived from `later.md`. Mirrors the issue, the investigated root cause, and an
implementation roadmap. `later.md` stays as the source of truth for the reported
behaviour.

## 1. Problem (from later.md)

- The front end React/Redux state is not maintained in a modern way: when a
  sale/purchase happens, analytics and dashboards that depend on the mutated
  data do not update dynamically (e.g. cashflow, product analytics, reports).
- The last commit (`5bfdee4 "Centralize shared RTK dashboard tags"`) claimed to
  fix this, but on the user's side nothing changed. It is half-baked.

## 2. Root cause

`store.ts` registers **66 independent `createApi` slices** (66 middlewares, one
reducer per slice). RTK Query tags are **scoped to the slice that declares
them** — automatic tag invalidation only works within a single API slice.

> Official doc (createApi, "API Slice Overview"): *"Typically, you should only
> have one API slice per base URL... This allows you to effectively take
> advantage of automated re-fetching by defining tag relationships across
> endpoints... Automatic tag invalidation only works within a single API slice.
> If you have multiple API slices, the automatic invalidation won't work across
> them. Every createApi call generates its own middleware, and each middleware
> added to the store will run checks against every dispatched action. That has
> a perf cost that adds up."*

Therefore:

- Commit `5bfdee4` added a shared tag *string* `'DashboardAPI'` and
  `withDashboard()` helpers to products/sales/purchases/inventory. The string
  is identical across files, but each `createApi` keeps its own cache, so the
  inert string never triggers a refetch in another slice. `apiTags.ts` only
  passes a string-valued test; it proves nothing about actual refetching.
- `cashFlowQuery.ts:28-33` declares `'FinanceAPI'` in its own `tagTypes` and
  even comments that the tag "is only ever *read* by financeQuery" — that is an
  incorrect belief. Listing it here cannot invalidate `financeQuery`'s cache.
- `saleReturnsQuery.ts:67-75` already documents the truth: *"RTK Query tags are
  scoped to the slice that declares them, so listing 'SalesAPI' in this slice's
  invalidatesTags is inert."* The workaround (lines 76-82) manually dispatches
  `salesQuery.util.invalidateTags`, `financeQuery.util.invalidateTags`, and
  `cashFlowQuery.util.invalidateTags` after every sale-return mutation. That is
  the only place in the app that tries to cross-refresh, and it is manual,
  incomplete (products, inventory and reports are not invalidated), and it lives
  only on returns — `salesQuery.addSale`, `purchasesQuery.addPurchase`,
  `receivePurchase`, etc. still only refresh their own slice.

### Concrete failure (the later.md example)

1. User records a sale of 2 products worth 200000 (`salesQuery.addSale`).
2. `addSale` invalidates `['SalesAPI', 'DashboardAPI']` — both provide on the
   sales slice only.
3. `ExecutiveAnalyticsPage` / `ExecutiveDashboardPage` show:
   - SalesAnalytics / RevenueChart / SalesStatCards → `useGetSalesAnalyticsQuery`
     (same slice, so **these do** refresh).
   - CashFlowAnalytics / CashPosition → `useCashFlowAnalyticsQuery`
     (`branchesQuery`, different slice — **stale**).
   - InventoryAnalytics / PerformanceMetrics → `useProductAnalyticsQuery` /
     `useProductMetricsQuery` (`branchProductsQuery`, different slice —
     **stale**).
   - StockHealth / TopProducts reports → `branchReportsQuery` (**stale**).
   - Finance dashboard/ledger → `financeQuery` / `cashFlowQuery` (**stale**).
4. Result: the user sees the sale recorded on the sales card but cashflow,
   inventory, product analytics and reports stay at pre-sale figures — exactly
   what `later.md` reports.

## 3. Verified consumer map (used to scope shared tags)

All hook usage below is from the executive dashboard/analytics/reports pages:

| Reader query | Slice | Used by |
| --- | --- | --- |
| `getSalesAnalytics` | `sales` | SalesAnalytics, SalesStatCards, RevenueChart, SalesPurchasesSummary |
| `getReturnAnalytics` | `sales` | ReturnAnalytics |
| `purchaseAnalytics` | `purchases` | PurchasesAnalytics, SalesPurchasesSummary |
| `cashFlowAnalytics` | `branches` (dashboard) | CashFlowAnalytics, CashPosition |
| `branchDynamics` | `branches` (dashboard) | dashboard dynamics |
| `productAnalytics` / `productMetrics` / `products` | `branch.products` | InventoryAnalytics, PerformanceMetrics, ProductTable |
| `productExpiring` / `productRestocking` | `branch.products` | StockHealth, SmartRestocking |
| `getInventory` | `inventory` | inventory page |
| `branchPerformance`, `stockSummary`, `lowStock`, `outOfStock`, `deadStock`, `inventoryValuation`, `salesByProduct`, `stockMovement`, `monthlyPerformance` | `branch.reports` | report pages, StockHealth, TopProducts |
| `getFinanceDashboard` | `finance` | FinanceOverview |
| `getCashFlows` | `cashFlow` | cash flow page |

Writers that must push new state into the readers above: `salesQuery`
(addSale/updateSale), `purchasesQuery` (add/update/delete/receive),
`saleReturnsQuery`, `purchaseReturnsQuery`, `branchProductsQuery`
(add/update/delete), `stockTransfersQuery`, `expenseQuery`, `salaryQuery`,
`financeQuery.createFinanceAdjustment`, `cashFlowQuery.setCashFlowDirection`,
`productAuditQuery`, `financialAuditQuery`.

Backend grounding (from git history) for wiring finance/cashflow refreshes:
- purchases are stock/price-affecting on completion (`e731372`,
  `88bc603`, `f950547`),
- expenses sync `cash_flow` on create/update/delete (`8fa1d55`).

## 4. Solution

Adopt the RTK Query **single base API** pattern (`baseApi` +
`injectEndpoints`), exactly what the docs prescribe for multiple domains that
must share tags.

- One `createApi` (`baseQuery` rooted at `VITE_BASE_URL`), declared once with
  the full shared tag registry.
- Each feature file keeps its own physical module and folder, but is converted
  from `createApi(...)` to `baseApi.injectEndpoints({ endpoints: ... })`.
  This satisfies the "maintain proper structure" and "maintain current base
  architecture" constraints — the folder tree and exported hook names stay the
  same; only the definition plumbing changes.
- Endpoint `url` strings must gain the resource prefix that today lives in
  per-slice `baseUrl` (e.g. `baseUrl: /products` → endpoint url `'/products'`
  against the root baseQuery). Mechanical, per-file.
- `store.ts` registers **one** reducer and **one** middleware
  (`baseApi.reducer` / `baseApi.middleware`) instead of 66; all injected
  endpoints ride on it. This also removes the documented perf cost.
- Replace the manual `invalidateRevenueSlices` dispatch workaround in
  `saleReturnsQuery.ts` with native shared tags (delete it).
- `apiTags.ts` becomes the real source of truth for the base API's `tagTypes`
  and centralizes the invalidation matrix. `withDashboard`/`sharedTagSets`
  survive only in the form actually wired into `invalidatesTags`.

### Proposed shared tag groups (final label values are a decision for the implementer)

Drop the `*API` suffix baking for new tags; keep existing string values to limit
churn (each rename is mechanical and audit-able). Group tags by domain:

- **Stock domain**: `ProductsAPI`, `InventoryAPI`, `StockTransfersAPI`,
  `ProductAuditAPI`, `PriceHistory`
- **Money domain**: `SalesAPI`, `SaleReturnsAPI`, `PurchasesAPI`,
  `PurchaseReturnsAPI`, `FinanceAPI`, `CashFlowAPI`, `ExpenseAPI`, `Salary`
- **Read-model domain**: `Reports`, `DashboardAPI`, `BranchesAPI`
  (branch/cashflow analytics live here)

Queries `providesTags` their own domain tag + `DashboardAPI` (the dashboard is a
derived read-model of the money and stock domains). Mutations `invalidatesTags`
their own tag + every read-model tag they change:

| Mutation | Invalidates |
| --- | --- |
| `addSale`, `updateSale` | SalesAPI, ProductsAPI, InventoryAPI, FinanceAPI, CashFlowAPI, Reports, DashboardAPI |
| `addSaleReturn`, `updateSaleReturn`, `deleteSaleReturn` | (+ SaleReturnsAPI on top of the sale row) |
| `addPurchase`, `updatePurchase`, `deletePurchase`, `receivePurchase` | PurchasesAPI, ProductsAPI, InventoryAPI, FinanceAPI, CashFlowAPI, Reports, DashboardAPI |
| `addPurchaseReturn` | (+ PurchaseReturnsAPI on top of the purchase row) |
| `addProduct`, `updateProduct`, `deleteProduct` | ProductsAPI, InventoryAPI, PriceHistory (on price change), Reports, DashboardAPI |
| stock transfer mutations | StockTransfersAPI, ProductsAPI, InventoryAPI, Reports, DashboardAPI |
| product-audit mutations | ProductAuditAPI, ProductsAPI, InventoryAPI, Reports, DashboardAPI |
| expense mutations | ExpenseAPI, FinanceAPI, CashFlowAPI, Reports, DashboardAPI |
| salary mutations | Salary, FinanceAPI, CashFlowAPI, Reports, DashboardAPI |
| `createFinanceAdjustment` | FinanceAPI, CashFlowAPI, DashboardAPI |
| `setCashFlowDirection` | CashFlowAPI, FinanceAPI, DashboardAPI |

The matrix is the *proposal*; each row must be confirmed against the endpoint
during Phase 0/2 (only include a tag when the mutation truly changes that
read-model).

## 5. Implementation phases

Constraints carried in: structure preserved, `VITE_BASE_URL` unchanged, no
hallucinated API shape — every migration step is verified by `tsc -b`, `npm run
lint`, and `npm test`.

**Phase 0 — Baseline proof (do first, keep as regression test).**
Write a failing test that demonstrates the bug: register two slices sharing a
tag string, run a mutation on slice A, assert slice B's providing query is
refetched. It fails today. Optionally assert the same for `salesQuery` →
`branchesQuery.cashFlowAnalytics`. This test doubles as the acceptance test
after the base API lands.

**Phase 1 — `baseApi`.**
- Add `ui/src/app/store/baseApi.ts`: `createApi` with
  `reducerPath: 'baseApi'`, `baseQuery: fetchBaseQuery({ baseUrl: VITE_BASE_URL,
  prepareHeaders })`, `endpoints: () => ({})`, empty `tagTypes`.
- Expand `apiTags.ts` with the full tag set; keep it the only place tags are
  named.

**Phase 2 — Migrate writers.**
Convert, in dependency order, the slices whose mutations drive everything else,
keeping every exported hook name:
`sales`, `purchases`, `sale-returns`, `purchase-returns`, `branch/products`,
`inventory`, `business/executive/stockTransfers`, audit queries. Each file:
`createApi(...)` → `baseApi.injectEndpoints({ endpoints: (builder) => ({...}) })`
and prefix resource paths. Re-export same hooks. Do not touch `store.ts` until
all slices share the base API (injectEndpoints can't be rolled back per slice).

**Phase 3 — Migrate readers.**
`finance`, `business/executive/cashFlow`, `branch/reports`, `business/branches`
(the dashboard/cashflow readers), `expense`, `salary`, `priceHistory`,
`branch/finances`, plus the self-contained domains the team decides to fold in.

**Phase 4 — Store.**
In `store.ts`, replace the 66 reducer entries with
`[baseApi.reducerPath]: baseApi.reducer` and the 66 middlewares with
`baseApi.middleware` (keep `authListenerMiddleware` and `apiErrorMiddleware`
last). Every imported `XQuery` symbol still needed for its hooks must remain as
an import in the pages; the slice objects themselves are no longer registered.

**Phase 5 — Cleanup.**
- Delete the manual `invalidateRevenueSlices` dispatch + the now-unused imports
  in `saleReturnsQuery`.
- Remove the inert `'FinanceAPI'` cross-slice bookkeeping in `cashFlowQuery`.
- Remove `withDashboard`/`sharedTagSets` if superseded by the real tags, or
  re-point them at the base API's tag constants; update `apiTags.test.ts` to
  assert real invalidation behaviour instead of string identity.
- Fold `invalidateRevenueSlices`-style behaviour for every other writer into the
  matrix above.

**Phase 6 — Verify.**
`npm run build` (runs `tsc -b`), `npm run lint -- --max-warnings=0` (or the
repo's existing baseline), `npm test`, then the manual acceptance scenario from
later.md: create two sales worth 200000 and confirm, without a page reload, that
CashFlowAnalytics/CashPosition, product/inventory analytics, and reports
advance alongside the sales card.

## 6. Open decisions for the implementer

1. **Scope of unification** — fold *all* 66 slices into the base API, or keep
   genuinely independent slices (e.g. public `countries`, marketing `plans`,
   `ai`, `todos`) separate? Recommendation: fold in every slice that
   participates in the money/stock/reporting domain; keep slices that are
   self-contained and tag-independent separate only if their base URL roots
   differ meaningfully — otherwise fold them too, since every extra
   slice/middleware is a perf and correctness tax.
2. **Tag labels** — keep today's `*API` string values vs. rename to short
   names. Keep-today is cheaper; rename is cleaner. Either way they must become
   constants in `apiTags.ts` only.
3. **`DashboardAPI` umbrella** — keep as an always-derived read-model tag, or
   enumerate reader tags explicitly per mutation. Both valid; the umbrella
   matches the last commit's intent and is simpler to maintain.
4. **Mechanical migration size** — the ~66 feature files each keep their folder
   and exports, so page-level imports (`@/app/store/features/branch`, etc.) are
   untouched; the change surface is per-file `createApi` → `injectEndpoints`
   plus the resource-prefix shift, and `store.ts` collapses to one registration.

## 7. Definition of done

- One RTK Query API slice is registered in the store that owns all shared tags.
- Every query/mutation that participates in shared state provides/invalidates
  through that single API, so cross-domain refresh is automatic (no manual
  `util.invalidateTags` dispatches, no inert tag strings).
- `apiTags.test.ts` proves refetch-across-domains (the Phase 0 test).
- The later.md example demonstrably passes end-to-end.
- `tsc -b`, lint, and vitest are green.