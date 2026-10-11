export const apiTags = {
  dashboard: 'DashboardAPI',
  products: 'ProductsAPI',
  sales: 'SalesAPI',
  purchases: 'PurchasesAPI',
  inventory: 'InventoryAPI',
  purchaseOrders: 'PurchaseOrdersAPI',
  finance: 'FinanceAPI',
  reports: 'Reports',
  customers: 'CustomersAPI',
  suppliers: 'SuppliersAPI',
} as const;

export const dashboardTags = [apiTags.dashboard] as const;

export const withDashboard = (...tags: string[]) => Array.from(new Set([...tags, apiTags.dashboard]));

export const sharedTagSets = {
  products: withDashboard(apiTags.products),
  sales: withDashboard(apiTags.sales),
  purchases: withDashboard(apiTags.purchases),
  inventory: withDashboard(apiTags.inventory),
} as const;
