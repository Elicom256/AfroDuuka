import { configureStore } from '@reduxjs/toolkit';
import { authQuery } from '../features/auth/authQuery';
import { workersQuery } from '../features/business/workers/workersQuery';
import { productsQuery } from '../features/business/products/productsQuery';
import { salesQuery } from '../features/branch/sales/salesQuery';
import { purchasesQuery } from '../features/branch/purchases/purchasesQuery';
import { customersQuery } from '../features/business/customers/customersQuery';
import { inventoryQuery } from '../features/business/inventory/inventoryQuery';
import { businessQuery } from '../features/business/setup/businessQuery';
import { rolesQuery } from '../features/business/roles/rolesQuery';
import { supplierQuery } from '../features/business/suppliers/supplierQuery';
import { branchesQuery } from '../features/business/branches/branchesQuery';
import { bsbranchProductsQuery } from '../features/branch/products/branchProductsQuery';
import { branchWorkersQuery } from '../features/branch/workers/branchWorkersQuery';
import { branchCustomersQuery } from '../features/branch/customers/branchCustomersQuery';
import { branchSuppliersQuery } from '../features/branch/suppliers/branchSuppliersQuery';
import { branchReportsQuery } from '../features/branch/reports/branchReportsQuery';
import { branchFinancesQuery } from '../features/branch/finances/branchFinancesQuery';
import { notificationsApi } from '../features/branch/notifications/notificationsQuery';
import { branchMessagesQuery } from '../features/branch/messages/messagesQuery';
import { branchPromotionsQuery } from '../features/branch/promotions/promotionsQuery';
import { branchAttendanceQuery } from '../features/branch/attendance/attendanceQuery';
import { branchHistoryQuery } from '../features/branch/history/historyQuery';
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
import { pricingQuery } from '../features/pricing/pricingQuery';
import { subscriptionsQuery } from '../features/subscriptions/subscriptionsQuery';

export const store = configureStore({
  reducer: {
    [authQuery.reducerPath]: authQuery.reducer,
    [workersQuery.reducerPath]: workersQuery.reducer,
    [productsQuery.reducerPath]: productsQuery.reducer,
    [salesQuery.reducerPath]: salesQuery.reducer,
    [purchasesQuery.reducerPath]: purchasesQuery.reducer,
    [customersQuery.reducerPath]: customersQuery.reducer,
    [inventoryQuery.reducerPath]: inventoryQuery.reducer,
    [businessQuery.reducerPath]: businessQuery.reducer,
    [rolesQuery.reducerPath]: rolesQuery.reducer,
    [supplierQuery.reducerPath]: supplierQuery.reducer,
    [branchesQuery.reducerPath]: branchesQuery.reducer,
    [bsbranchProductsQuery.reducerPath]: bsbranchProductsQuery.reducer,
    [branchWorkersQuery.reducerPath]: branchWorkersQuery.reducer,
    [branchCustomersQuery.reducerPath]: branchCustomersQuery.reducer,
    [branchSuppliersQuery.reducerPath]: branchSuppliersQuery.reducer,
    [branchReportsQuery.reducerPath]: branchReportsQuery.reducer,
    [branchFinancesQuery.reducerPath]: branchFinancesQuery.reducer,
    [branchMessagesQuery.reducerPath]: branchMessagesQuery.reducer,
    [branchPromotionsQuery.reducerPath]: branchPromotionsQuery.reducer,
    [branchAttendanceQuery.reducerPath]: branchAttendanceQuery.reducer,
    [branchHistoryQuery.reducerPath]: branchHistoryQuery.reducer,
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
    [pricingQuery.reducerPath]: pricingQuery.reducer,
    [subscriptionsQuery.reducerPath]: subscriptionsQuery.reducer,
  },

  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      authQuery.middleware,
      workersQuery.middleware,
      productsQuery.middleware,
      salesQuery.middleware,
      purchasesQuery.middleware,
      customersQuery.middleware,
      inventoryQuery.middleware,
      businessQuery.middleware,
      rolesQuery.middleware,
      supplierQuery.middleware,
      branchesQuery.middleware,
      bsbranchProductsQuery.middleware,
      branchWorkersQuery.middleware,
      branchCustomersQuery.middleware,
      branchSuppliersQuery.middleware,
      branchReportsQuery.middleware,
      branchFinancesQuery.middleware,
      branchMessagesQuery.middleware,
      branchPromotionsQuery.middleware,
      branchAttendanceQuery.middleware,
      branchHistoryQuery.middleware,
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
      pricingQuery.middleware,
      subscriptionsQuery.middleware,
    ),
});

// Infer the `RootState` and `AppDispatch` types from the store itself
export type RootState = ReturnType<typeof store.getState>;
// Inferred type: {posts: PostsState, comments: CommentsState, users: UsersState}
export type AppDispatch = typeof store.dispatch;
