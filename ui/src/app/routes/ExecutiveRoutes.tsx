import { ExecutiveLayout } from '../pages/dashboards/executive/ExecutiveLayout';
import { ExecutiveDashboardPage } from '../pages/dashboards/executive/pages/ExecutiveDashboardPage';
import { ExecutiveWorkersPage } from '../pages/dashboards/executive/pages/ExecutiveWorkersPage';
import { ExecutiveProductsPage } from '../pages/dashboards/executive/pages/ExecutiveProductsPage';
import { ExecutiveOrdersPage } from '../pages/dashboards/executive/pages/ExecutiveOrdersPage';
import { QuotationsPage } from '../pages/dashboards/shared/quotations/QuotationsPage';
import { ExecutiveSalesPage } from '../pages/dashboards/executive/pages/ExecutiveSalesPage';
import { ExecutivePurchasesPage } from '../pages/dashboards/executive/pages/ExecutivePurchasesPage';
import { ExecutiveSettingsPage } from '../pages/dashboards/executive/pages/settings';
import { PaymentSettings } from '../pages/dashboards/executive/components/settings/PaymentSettings';
import { CustomerSettings } from '../pages/dashboards/executive/components/settings/CustomerSettings';
import { ReportsSettings } from '../pages/dashboards/executive/components/settings/ReportsSettings';
import { PromotionsSettings } from '../pages/dashboards/executive/components/settings/PromotionsSettings';
import { AttendanceSettings } from '../pages/dashboards/executive/components/settings/AttendanceSettings';
import { AddBusinessForm } from '../pages/dashboards/executive/components/AddBusinessForm';
import { Product } from '../pages/dashboards/executive/components/products/Product';
import { Sale } from '../pages/dashboards/executive/components/sales/Sale';
import { Purchase } from '../pages/dashboards/executive/components/purchases/Purchase';
import { SaleReturn } from '../pages/dashboards/executive/components/sale-returns/SaleReturn';
import { PurchaseReturn } from '../pages/dashboards/executive/components/purchase-returns/PurchaseReturn';
import { ExecutiveSaleReturnsPage } from '../pages/dashboards/executive/pages/ExecutiveSaleReturnsPage';
import { ExecutivePurchaseReturnsPage } from '../pages/dashboards/executive/pages/ExecutivePurchaseReturnsPage';
import { BusinessBranches } from '../pages/dashboards/executive/pages/BusinessBranches';
import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { ExecutiveMessagesPage } from '../pages/dashboards/executive/pages/ExecutiveMessagesPage';
import { ExecutiveNotificationsPage } from '../pages/dashboards/executive/pages/ExecutiveNotificationsPage';
import { NotificationDetailPage } from '../pages/dashboards/shared/notifications/NotificationDetailPage';
import { ProductCategories } from '../pages/dashboards/executive/components/products/ProductCategories';
import { ProductCategory } from '../pages/dashboards/executive/components/products/ProductCategory';
import { ProtectedRoutes } from './ProtectedRoutes';
import { PosPage } from '../pages/dashboards/shared/pos/PosPage';
import { ExecutiveSuppliersPage } from '../pages/dashboards/executive/pages/ExecutiveSuppliersPage';

import { ExecutiveCustomersPage } from '../pages/dashboards/executive/pages/ExecutiveCustomersPage';
import { ExecutiveAnalyticsPage } from '../pages/dashboards/executive/pages/ExecutiveAnalyticsPage';
import { ExecutiveReportsPage } from '../pages/dashboards/executive/pages/ExecutiveReportsPage';
import { ExecutiveFinanceTransactionsPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceTransactionsPage';
import { ExecutiveFinanceReportsPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceReportsPage';
import { ExecutiveFinanceCashFlowPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceCashFlowPage';
import { ExecutiveEmployeeRemunerationPage } from '../pages/dashboards/executive/pages/ExecutiveEmployeeRemunerationPage';
import { ExecutiveEmployeeSalaryPage } from '../pages/dashboards/executive/pages/ExecutiveEmployeeSalaryPage';
import { ExecutiveAttendancePage } from '../pages/dashboards/executive/pages/ExecutiveAttendancePage';
import { ExecutivePromotionsPage } from '../pages/dashboards/executive/pages/ExecutivePromotionsPage';
import { ExecutiveCouponsPage } from '../pages/dashboards/executive/pages/ExecutiveCouponsPage';

import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Attendance } from '../pages/dashboards/executive/components/attendance/Attendance';
import { Worker } from '../pages/dashboards/executive/components/workers/Worker';
import { Supplier } from '../pages/dashboards/executive/components/suppliers/Supplier';
import { Customer } from '../pages/dashboards/executive/components/customers/Customer';
import { TodoList } from '../pages/dashboards/executive/components/todos/TodoList';
import { TodoForm } from '../pages/dashboards/executive/components/todos/TodoForm';
import SupplierSettings from '../pages/dashboards/executive/components/settings/SupplierSettings';
import { CurrencySettings } from '../pages/dashboards/executive/components/settings/CurrencySettings';
import { BusinessInfoSettings } from '../pages/dashboards/executive/components/settings/BusinessInfoSettings';
import { PlanBillingSettings } from '../pages/dashboards/executive/components/settings/PlanBillingSettings';
import { ExecutiveCurrencyRatesPage } from '../pages/dashboards/executive/pages/ExecutiveCurrencyRatesPage';
import { WhatsAppSettings } from '../pages/dashboards/executive/components/settings/WhatsAppSettings';
import { ExecutivePrintersPage } from '../pages/dashboards/executive/pages/ExecutivePrintersPage';
import { ExecutiveStockTransfersPage } from '../pages/dashboards/executive/pages/ExecutiveStockTransfersPage';
import { ExecutiveReceiptsPage } from '../pages/dashboards/executive/pages/ExecutiveReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/executive/components/receipts/Receipt';
import { ExecutiveExpenseCategoriesPage } from '../pages/dashboards/executive/pages/ExecutiveExpenseCategoriesPage';
import { ExecutiveExpensesPage } from '../pages/dashboards/executive/pages/ExecutiveExpensesPage';
import { ExecutiveReorderRulesPage } from '../pages/dashboards/executive/pages/ExecutiveReorderRulesPage';
import { ExecutiveSubscriptionPaymentsPage } from '../pages/dashboards/executive/pages/ExecutiveSubscriptionPaymentsPage';
import { ExecutiveReportExportsPage } from '../pages/dashboards/executive/pages/ExecutiveReportExportsPage';
import { ExecutiveProductAuditsPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditsPage';
import { ExecutiveProductAuditPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditPage';
import { ExecutiveProductAuditReportPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditReportPage';
import { ExecutiveFinancialAuditsPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditsPage';
import { ExecutiveFinancialAuditPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditPage';
import { ExecutiveFinancialAuditReportPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditReportPage';
import { ExecutiveTaxPage } from '../pages/dashboards/executive/pages/ExecutiveTaxPage';
import { ActivityLogPage } from '../pages/dashboards/shared/activity-log/ActivityLogPage';
import { ProcurementRoutes } from './ProcurementRoutes';

export const ExecutiveRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route element={<ProtectedRoutes />}>
        <Route path='pos' element={<PosPage />} />
        <Route element={<ExecutiveLayout />}>
          <Route index element={<ExecutiveDashboardPage />} />

          <Route path='workers' element={<ExecutiveWorkersPage />} />
          <Route path='workers/:id' element={<Worker />} />
          <Route path='suppliers' element={<ExecutiveSuppliersPage />} />
          <Route path='suppliers/:id' element={<Supplier />} />

          <Route path='create-business' element={<AddBusinessForm />} />
          <Route path='products' element={<ExecutiveProductsPage />} />
          <Route path='product-categories' element={<ProductCategories />} />
          <Route path='product-categories/:id' element={<ProductCategory />} />
          <Route path='sales' element={<ExecutiveSalesPage />} />
          <Route path='sales/:id' element={<Sale />} />
          <Route path='sale-returns' element={<ExecutiveSaleReturnsPage />} />
          <Route path='sale-returns/:id' element={<SaleReturn />} />
          <Route path='purchases' element={<ExecutivePurchasesPage />} />
          <Route path='purchases/:id' element={<Purchase />} />
          <Route path='purchase-returns' element={<ExecutivePurchaseReturnsPage />} />
          <Route path='purchase-returns/:id' element={<PurchaseReturn />} />
          <Route path='orders' element={<ExecutiveOrdersPage />} />
          <Route path='quotations' element={<QuotationsPage />} />
          <Route path='customers' element={<ExecutiveCustomersPage />} />
          <Route path='customers/:id' element={<Customer />} />

          <Route path='analytics' element={<ExecutiveAnalyticsPage />} />
          <Route path='todos' element={<TodoList />} />
          <Route path='create-todo' element={<TodoForm />} />
          <Route path='reports' element={<ExecutiveReportsPage />} />
          <Route path='finance/transactions' element={<ExecutiveFinanceTransactionsPage />} />
          <Route path='finance/reports' element={<ExecutiveFinanceReportsPage />} />
          <Route path='finance/cashflow' element={<ExecutiveFinanceCashFlowPage />} />
          <Route path='cashflow' element={<ExecutiveFinanceCashFlowPage />} />
          <Route path='attendance' element={<ExecutiveAttendancePage />} />
          <Route path='attendance/:id' element={<Attendance />} />
          <Route path='employee-salaries' element={<ExecutiveEmployeeSalaryPage />} />
          <Route path='remuneration' element={<ExecutiveEmployeeRemunerationPage />} />
          <Route path='promotions' element={<ExecutivePromotionsPage />} />
          <Route path='coupons' element={<ExecutiveCouponsPage />} />
          <Route path='messages' element={<ExecutiveMessagesPage />} />
          <Route path='notifications' element={<ExecutiveNotificationsPage />} />
          <Route path='notifications/:id' element={<NotificationDetailPage scope='executive' />} />

          <Route path='settings' element={<ExecutiveSettingsPage />}>
            <Route index element={<BusinessInfoSettings />} />
            <Route path='plan-billing' element={<PlanBillingSettings />} />
            <Route path='business-info' element={<BusinessInfoSettings />} />
            <Route path='payment-settings' element={<PaymentSettings />} />
            <Route path='customer-settings' element={<CustomerSettings />} />
            <Route path='reports-settings' element={<ReportsSettings />} />
            <Route path='promotions-settings' element={<PromotionsSettings />} />
            <Route path='attendance-settings' element={<AttendanceSettings />} />
            <Route path='supplier-settings' element={<SupplierSettings />} />
            <Route path='currency-settings' element={<CurrencySettings />} />
            <Route path='whatsapp-settings' element={<WhatsAppSettings />} />
          </Route>
          <Route path='currency-rates' element={<ExecutiveCurrencyRatesPage />} />
          <Route path='printers' element={<ExecutivePrintersPage />} />
          <Route path='stock-transfers' element={<ExecutiveStockTransfersPage />} />
          <Route path='reorder-rules' element={<ExecutiveReorderRulesPage />} />
          <Route path='report-exports' element={<ExecutiveReportExportsPage />} />
          <Route
            path='activity-log'
            element={
              <ActivityLogPage scope='business' title='Activity Log' subtitle='Track all business activity and changes' live />
            }
          />
          <Route path='products/:id' element={<Product />} />
          <Route path='receipts' element={<ExecutiveReceiptsPage />} />
          <Route path='receipts/:id' element={<ReceiptDetail />} />
          <Route path='expense-categories' element={<ExecutiveExpenseCategoriesPage />} />
          <Route path='expenses' element={<ExecutiveExpensesPage />} />
          <Route path='branches' element={<BusinessBranches />} />
          <Route path='subscriptions' element={<ExecutiveSubscriptionPaymentsPage />} />
          <Route path='product-audits' element={<ExecutiveProductAuditsPage />} />
          <Route path='product-audits/:id' element={<ExecutiveProductAuditPage />} />
          <Route path='product-audits/:id/report' element={<ExecutiveProductAuditReportPage />} />
          <Route path='financial-audits' element={<ExecutiveFinancialAuditsPage />} />
          <Route path='financial-audits/:id' element={<ExecutiveFinancialAuditPage />} />
          <Route path='financial-audits/:id/report' element={<ExecutiveFinancialAuditReportPage />} />
          <Route path='tax' element={<ExecutiveTaxPage />} />
          <Route path='procurement/*' element={<ProcurementRoutes />} />
        </Route>
        <Route path='*' element={<NotFound />} />
      </Route>
    </Routes>
  );
};
