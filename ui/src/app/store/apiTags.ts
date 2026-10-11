/**
 * Central registry of every RTK Query tag that must be shared across the base
 * API slice.
 *
 * RTK Query automatic tag invalidation only works within a single API slice
 * (see https://redux.js.org/toolkit/rtk-query/api/createApi). This app used to
 * define one slice per feature (~66 createApi calls); a shared tag *string*
 * declared in two different slices never invalidated across them, which is why
 * dashboards and analytics stayed stale after a sale or purchase. All the slices
 * that read and write the same money/stock/reporting read-models are now
 * injected into `baseApi` (see ./baseApi.ts) so these tags actually work.
 *
 * Grouping:
 *  - stock domain ... products, inventory, stockTransfers, productAudit, priceHistory
 *  - money domain .. sales, purchases, saleReturns, purchaseReturns, finance, cashflow, expenses, salary
 *  - read model .... reports, branches (dashboard / cashflow analytics), dashboard (umbrella)
 */
export const apiTags = {
  // stock domain
  products: 'ProductsAPI',
  inventory: 'InventoryAPI',
  stockTransfers: 'StockTransfersAPI',
  productAudit: 'ProductAuditAPI',
  priceHistory: 'PriceHistory',
  // money domain
  sales: 'SalesAPI',
  purchases: 'PurchasesAPI',
  saleReturns: 'SaleReturnsAPI',
  purchaseReturns: 'PurchaseReturnsAPI',
  finance: 'FinanceAPI',
  cashflow: 'CashFlowAPI',
  expenses: 'ExpenseAPI',
  salary: 'Salary',
  // read model
  reports: 'Reports',
  branches: 'BranchesAPI',
  dashboard: 'DashboardAPI',
} as const;

/**
 * Every tag the base API slice declares up front, so no `invalidatesTags`
 * target is ever unregistered — invalidating a tag that is not in `tagTypes`
 * would warn and do nothing.
 */
export const sharedApiTagTypes = [
  apiTags.products,
  apiTags.inventory,
  apiTags.stockTransfers,
  apiTags.productAudit,
  apiTags.priceHistory,
  apiTags.sales,
  apiTags.purchases,
  apiTags.saleReturns,
  apiTags.purchaseReturns,
  apiTags.finance,
  apiTags.cashflow,
  apiTags.expenses,
  apiTags.salary,
  apiTags.reports,
  apiTags.branches,
  apiTags.dashboard,
] as const;

/** The read-model umbrella: anything that backs a dashboard or analytic query. */
export const dashboardTags = [apiTags.dashboard] as const;

/**
 * A tag list plus the dashboard umbrella, for queries that feed a dashboard
 * read-model. Used by both `providesTags` (queries) and `invalidatesTags`
 * (mutations).
 *
 * Generic over the passed tag literals so RTK Query's `ResultDescription` type
 * (which is narrowed to the slice's `tagTypes`) still accepts the result.
 */
export function withDashboard<T extends string>(...tags: T[]): (T | typeof apiTags.dashboard)[] {
  return Array.from(new Set([...tags, apiTags.dashboard])) as (T | typeof apiTags.dashboard)[];
}