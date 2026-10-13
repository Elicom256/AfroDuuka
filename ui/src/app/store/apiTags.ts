/**
 * Central registry of every RTK Query tag used in the app.
 *
 * RTK Query automatic tag invalidation only works within a single API slice
 * (see https://redux.js.org/toolkit/rtk-query/api/createApi). Every feature slice
 * is injected into `baseApi` (see ./baseApi.ts) so these tags actually work:
 * invalidating a tag one endpoint provides refetches every endpoint that
 * provides it, across feature boundaries.
 *
 * `tagTypes` has to be declared up front on the slice — `injectEndpoints` cannot
 * add new tag types — so every tag any feature uses is listed here. A tag used
 * in `providesTags` / `invalidatesTags` but missing from this registry is a type
 * error, which is why the list is kept in one place.
 *
 * Grouping:
 *  - stock domain ... products, inventory, stockTransfers, productAudit, priceHistory
 *  - money domain .. sales, purchases, saleReturns, purchaseReturns, finance, cashflow, expenses, salary
 *  - audits ......... financialAudit
 *  - read model .... reports, branches (dashboard / cashflow analytics), dashboard (umbrella)
 *  - features ...... every remaining feature slice's own tag
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
  // audits
  financialAudit: 'FinancialAuditAPI',
  // read model
  reports: 'Reports',
  branches: 'BranchesAPI',
  dashboard: 'DashboardAPI',
  // feature tags
  coupons: 'CouponsAPI',
  ai: 'AiAPI',
  orders: 'OrdersAPI',
  quotations: 'QuotationsAPI',
  purchaseOrders: 'PurchaseOrdersAPI',
  users: 'UsersAPI',
  plans: 'PlansAPI',
  executivePlans: 'ExecutivePlansAPI',
  todos: 'TodosAPI',
  branchWorkers: 'BranchWorkersAPI',
  procurement: 'Procurement',
  whatsapp: 'WhatsApp',
  customers: 'CustomersAPI',
  executiveAttendance: 'ExecutiveAttendanceAPI',
  printers: 'PrintersAPI',
  suppliers: 'SuppliersAPI',
  executiveRemuneration: 'ExecutiveRemunerationAPI',
  paymentGateways: 'PaymentGatewaysAPI',
  productCategories: 'ProductCategoriesAPI',
  activityLog: 'ActivityLogAPI',
  expenseCategories: 'ExpenseCategoriesAPI',
  currencyRates: 'CurrencyRatesAPI',
  reportExports: 'ReportExportsAPI',
  taxCategories: 'TaxCategoriesAPI',
  taxRates: 'TaxRatesAPI',
  taxPayments: 'TaxPaymentsAPI',
  reorderRules: 'ReorderRulesAPI',
  superAdminBusinesses: 'SuperAdminBusinessesAPI',
  reportsSettings: 'ReportsSettingsAPI',
  workers: 'WorkersAPI',
  supplierSettings: 'SupplierSettingsAPI',
  attendanceSettings: 'AttendanceSettingsAPI',
  customerSettings: 'CustomerSettingsAPI',
  promotionsSettings: 'PromotionsSettingsAPI',
  paymentSettings: 'PaymentSettingsAPI',
  pos: 'PosAPI',
  heldSales: 'HeldSales',
  onboarding: 'OnboardingApi',
  business: 'BusinessApi',
  branchPromotions: 'BranchPromotionsAPI',
  roles: 'RolesAPI',
  countries: 'Countries',
  receipts: 'ReceiptsAPI',
  subscriptions: 'SubscriptionsAPI',
  subscriptionPayments: 'SubscriptionPaymentsAPI',
  branchSuppliers: 'BranchSuppliersAPI',
  notifications: 'Notifications',
  branchCustomers: 'BranchCustomersAPI',
  branchAttendance: 'BranchAttendanceAPI',
  productAttachments: 'ProductAttachments',
} as const;

/**
 * Every tag the base API slice declares up front, so no `invalidatesTags`
 * target is ever unregistered — invalidating a tag that is not in `tagTypes`
 * would warn and do nothing.
 */
export const sharedApiTagTypes = Object.values(apiTags);

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
