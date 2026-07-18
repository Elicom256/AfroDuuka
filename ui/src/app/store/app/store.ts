import { configureStore } from '@reduxjs/toolkit';
import { authQuery } from '../features/auth/authQuery';
import { workersQuery } from '../features/business/workers/workersQuery';
import { productCategoriesQuery } from '../features/business/products/productsQuery';
import { salesQuery } from '../features/branch/sales/salesQuery';
import { purchasesQuery } from '../features/branch/purchases/purchasesQuery';
import { saleReturnsQuery } from '../features/branch/sale-returns/saleReturnsQuery';
import { purchaseReturnsQuery } from '../features/branch/purchase-returns/purchaseReturnsQuery';
import { customersQuery } from '../features/business/customers/customersQuery';
import { inventoryQuery } from '../features/business/inventory/inventoryQuery';
import { businessQuery } from '../features/business/setup/businessQuery';
import { rolesQuery } from '../features/business/roles/rolesQuery';
import { supplierQuery } from '../features/business/suppliers/supplierQuery';
import { branchesQuery } from '../features/business/branches/branchesQuery';
import { productsQuery as branchProductsQuery } from '../features/branch/products/branchProductsQuery';
import { branchWorkersQuery } from '../features/branch/workers/branchWorkersQuery';
import { branchCustomersQuery } from '../features/branch/customers/branchCustomersQuery';
import { branchSuppliersQuery } from '../features/branch/suppliers/branchSuppliersQuery';
import { branchReportsQuery } from '../features/branch/reports/branchReportsQuery';
import { branchFinancesQuery } from '../features/branch/finances/branchFinancesQuery';
import { notificationsApi } from '../features/branch/notifications/notificationsQuery';
import { branchMessagesQuery } from '../features/branch/messages/messagesQuery';
import { branchPromotionsQuery } from '../features/branch/promotions/promotionsQuery';
import { branchAttendanceQuery } from '../features/branch/attendance/attendanceQuery';
import { attendanceSettingsQuery } from '../features/business/settings/attendance';
import { customerSettingsQuery } from '../features/business/settings/customer';
import { paymentSettingsQuery } from '../features/business/settings/payment';
import { promotionsSettingsQuery } from '../features/business/settings/promotions';
import { reportsSettingsQuery } from '../features/business/settings/reports';
import { supplierSettingsQuery } from '../features/business/settings/supplier';
import { adminAttendanceQuery } from '../features/business/admin/attendanceQuery';
import { adminTaxesQuery } from '../features/business/admin/taxesQuery';
import { adminEmployeeRemunerationQuery } from '../features/business/admin/employeeRemunerationQuery';
import { adminBusinessActivityLogsQuery } from '../features/business/admin/businessActivityLogsQuery';
import { currencyRatesQuery } from '../features/business/admin/currencyRatesQuery';
import { paymentGatewaysQuery } from '../features/business/admin/paymentGatewaysQuery';
import { printersQuery } from '../features/business/admin/printersQuery';
import { stockTransfersQuery } from '../features/business/admin/stockTransfersQuery';
import { reorderRulesQuery } from '../features/business/admin/reorderRulesQuery';
import { taxInvoicesQuery } from '../features/business/admin/taxInvoicesQuery';
import { loyaltyQuery } from '../features/business/admin/loyaltyQuery';
import { employeeSalaryQuery } from '../features/business/admin/employeeSalaryQuery';
import { countriesQuery } from '../features/countries/countriesQuery';
import { cashFlowQuery } from '../features/business/admin/cashFlowQuery';
import { aiQuery } from '../features/ai/aiQuery';
import { todoQuery } from '../features/todos/todoQuery';
import { plansQuery } from '../features/plans/plansQuery';
import { adminPlansQuery } from '../features/plans/adminPlansQuery';
import { subscriptionsQuery } from '../features/subscriptions/subscriptionsQuery';
import { subscriptionPaymentsQuery } from '../features/subscriptions/subscriptionPaymentsQuery';
import { superAdminBusinessesQuery } from '../features/business/superAdminBusinessesQuery';
import { expenseCategoriesQuery } from '../features/business/admin/expenseCategoriesQuery';
import { expenseQuery } from '../features/business/admin/expenseQuery';
import { priceHistoryQuery } from '../features/branch/priceHistory/priceHistoryQuery';
import { receiptsQuery } from '../features/branch/receipts/receiptsQuery';
import { posQuery } from '../features/branch/pos/posQuery';
import { ordersQuery } from '../features/orders/ordersQuery';
import { couponsQuery } from '../features/coupons/couponsQuery';

export const store = configureStore({
  reducer: {
    [authQuery.reducerPath]: authQuery.reducer,
    [workersQuery.reducerPath]: workersQuery.reducer,
    [productCategoriesQuery.reducerPath]: productCategoriesQuery.reducer,
    [salesQuery.reducerPath]: salesQuery.reducer,
    [purchasesQuery.reducerPath]: purchasesQuery.reducer,
    [saleReturnsQuery.reducerPath]: saleReturnsQuery.reducer,
    [purchaseReturnsQuery.reducerPath]: purchaseReturnsQuery.reducer,
    [customersQuery.reducerPath]: customersQuery.reducer,
    [inventoryQuery.reducerPath]: inventoryQuery.reducer,
    [businessQuery.reducerPath]: businessQuery.reducer,
    [rolesQuery.reducerPath]: rolesQuery.reducer,
    [supplierQuery.reducerPath]: supplierQuery.reducer,
    [branchesQuery.reducerPath]: branchesQuery.reducer,
    [branchProductsQuery.reducerPath]: branchProductsQuery.reducer,
    [branchWorkersQuery.reducerPath]: branchWorkersQuery.reducer,
    [branchCustomersQuery.reducerPath]: branchCustomersQuery.reducer,
    [branchSuppliersQuery.reducerPath]: branchSuppliersQuery.reducer,
    [branchReportsQuery.reducerPath]: branchReportsQuery.reducer,
    [branchFinancesQuery.reducerPath]: branchFinancesQuery.reducer,
    [branchMessagesQuery.reducerPath]: branchMessagesQuery.reducer,
    [branchPromotionsQuery.reducerPath]: branchPromotionsQuery.reducer,
    [branchAttendanceQuery.reducerPath]: branchAttendanceQuery.reducer,
    [adminAttendanceQuery.reducerPath]: adminAttendanceQuery.reducer,
    [adminTaxesQuery.reducerPath]: adminTaxesQuery.reducer,
    [adminEmployeeRemunerationQuery.reducerPath]: adminEmployeeRemunerationQuery.reducer,
    [adminBusinessActivityLogsQuery.reducerPath]: adminBusinessActivityLogsQuery.reducer,
    [currencyRatesQuery.reducerPath]: currencyRatesQuery.reducer,
    [paymentGatewaysQuery.reducerPath]: paymentGatewaysQuery.reducer,
    [printersQuery.reducerPath]: printersQuery.reducer,
    [stockTransfersQuery.reducerPath]: stockTransfersQuery.reducer,
    [reorderRulesQuery.reducerPath]: reorderRulesQuery.reducer,
    [taxInvoicesQuery.reducerPath]: taxInvoicesQuery.reducer,
    [loyaltyQuery.reducerPath]: loyaltyQuery.reducer,
    [employeeSalaryQuery.reducerPath]: employeeSalaryQuery.reducer,
    [attendanceSettingsQuery.reducerPath]: attendanceSettingsQuery.reducer,
    [customerSettingsQuery.reducerPath]: customerSettingsQuery.reducer,
    [paymentSettingsQuery.reducerPath]: paymentSettingsQuery.reducer,
    [promotionsSettingsQuery.reducerPath]: promotionsSettingsQuery.reducer,
    [reportsSettingsQuery.reducerPath]: reportsSettingsQuery.reducer,
    [supplierSettingsQuery.reducerPath]: supplierSettingsQuery.reducer,
    [notificationsApi.reducerPath]: notificationsApi.reducer,
    [countriesQuery.reducerPath]: countriesQuery.reducer,
    [cashFlowQuery.reducerPath]: cashFlowQuery.reducer,
    [aiQuery.reducerPath]: aiQuery.reducer,
    [todoQuery.reducerPath]: todoQuery.reducer,
    [plansQuery.reducerPath]: plansQuery.reducer,
    [adminPlansQuery.reducerPath]: adminPlansQuery.reducer,
    [subscriptionsQuery.reducerPath]: subscriptionsQuery.reducer,
    [subscriptionPaymentsQuery.reducerPath]: subscriptionPaymentsQuery.reducer,
    [superAdminBusinessesQuery.reducerPath]: superAdminBusinessesQuery.reducer,
    [expenseCategoriesQuery.reducerPath]: expenseCategoriesQuery.reducer,
    [expenseQuery.reducerPath]: expenseQuery.reducer,
    [priceHistoryQuery.reducerPath]: priceHistoryQuery.reducer,
    [receiptsQuery.reducerPath]: receiptsQuery.reducer,
    [posQuery.reducerPath]: posQuery.reducer,
    [ordersQuery.reducerPath]: ordersQuery.reducer,
    [couponsQuery.reducerPath]: couponsQuery.reducer,
  },

  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      authQuery.middleware,
      workersQuery.middleware,
      productCategoriesQuery.middleware,
      salesQuery.middleware,
      purchasesQuery.middleware,
      saleReturnsQuery.middleware,
      purchaseReturnsQuery.middleware,
      customersQuery.middleware,
      inventoryQuery.middleware,
      businessQuery.middleware,
      rolesQuery.middleware,
      supplierQuery.middleware,
      branchesQuery.middleware,
      branchProductsQuery.middleware,
      branchWorkersQuery.middleware,
      branchCustomersQuery.middleware,
      branchSuppliersQuery.middleware,
      branchReportsQuery.middleware,
      branchFinancesQuery.middleware,
      branchMessagesQuery.middleware,
      branchPromotionsQuery.middleware,
      branchAttendanceQuery.middleware,
      adminAttendanceQuery.middleware,
      adminTaxesQuery.middleware,
      adminEmployeeRemunerationQuery.middleware,
      adminBusinessActivityLogsQuery.middleware,
      currencyRatesQuery.middleware,
      paymentGatewaysQuery.middleware,
      printersQuery.middleware,
      stockTransfersQuery.middleware,
      reorderRulesQuery.middleware,
      taxInvoicesQuery.middleware,
      loyaltyQuery.middleware,
      employeeSalaryQuery.middleware,
      attendanceSettingsQuery.middleware,
      customerSettingsQuery.middleware,
      paymentSettingsQuery.middleware,
      promotionsSettingsQuery.middleware,
      reportsSettingsQuery.middleware,
      supplierSettingsQuery.middleware,
      notificationsApi.middleware,
      countriesQuery.middleware,
      cashFlowQuery.middleware,
      aiQuery.middleware,
      todoQuery.middleware,
      plansQuery.middleware,
      adminPlansQuery.middleware,
      subscriptionsQuery.middleware,
      subscriptionPaymentsQuery.middleware,
      superAdminBusinessesQuery.middleware,
      expenseCategoriesQuery.middleware,
      expenseQuery.middleware,
      priceHistoryQuery.middleware,
      receiptsQuery.middleware,
      posQuery.middleware,
      ordersQuery.middleware,
      couponsQuery.middleware,
    ),
});

// Infer the `RootState` and `AppDispatch` types from the store itself
export type RootState = ReturnType<typeof store.getState>;
// Inferred type: {posts: PostsState, comments: CommentsState, users: UsersState}
export type AppDispatch = typeof store.dispatch;
