import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { BranchManagerLayout } from '../pages/dashboards/branch-manager/BranchManagerLayout';
import { BranchManagerDashboardPage } from '../pages/dashboards/branch-manager/BranchManagerDashboardPage';
import { ExecutiveWorkersPage } from '../pages/dashboards/executive/pages/ExecutiveWorkersPage';
import { ExecutiveProductsPage } from '../pages/dashboards/executive/pages/ExecutiveProductsPage';
import { ExecutiveOrdersPage } from '../pages/dashboards/executive/pages/ExecutiveOrdersPage';
import { QuotationsPage } from '../pages/dashboards/shared/quotations/QuotationsPage';
import { ExecutiveSalesPage } from '../pages/dashboards/executive/pages/ExecutiveSalesPage';
import { ExecutivePurchasesPage } from '../pages/dashboards/executive/pages/ExecutivePurchasesPage';
import { Product } from '../pages/dashboards/executive/components/products/Product';
import { Sale } from '../pages/dashboards/executive/components/sales/Sale';
import { Purchase } from '../pages/dashboards/executive/components/purchases/Purchase';
import { SaleReturn } from '../pages/dashboards/executive/components/sale-returns/SaleReturn';
import { PurchaseReturn } from '../pages/dashboards/executive/components/purchase-returns/PurchaseReturn';
import { ExecutiveSaleReturnsPage } from '../pages/dashboards/executive/pages/ExecutiveSaleReturnsPage';
import { ExecutivePurchaseReturnsPage } from '../pages/dashboards/executive/pages/ExecutivePurchaseReturnsPage';
import { ExecutiveMessagesPage } from '../pages/dashboards/executive/pages/ExecutiveMessagesPage';
import { ExecutiveNotificationsPage } from '../pages/dashboards/executive/pages/ExecutiveNotificationsPage';
import { NotificationDetailPage } from '../pages/dashboards/shared/notifications/NotificationDetailPage';
import { ExecutiveCustomersPage } from '../pages/dashboards/executive/pages/ExecutiveCustomersPage';
import { ExecutiveAnalyticsPage } from '../pages/dashboards/executive/pages/ExecutiveAnalyticsPage';
import { ExecutiveReportsPage } from '../pages/dashboards/executive/pages/ExecutiveReportsPage';
import { ExecutiveFinancesPage } from '../pages/dashboards/executive/pages/ExecutiveFinancesPage';
import { ExecutiveFinanceTransactionsPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceTransactionsPage';
import { ExecutiveFinanceReportsPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceReportsPage';
import { ExecutiveFinanceCashFlowPage } from '../pages/dashboards/executive/pages/ExecutiveFinanceCashFlowPage';
import { ExecutiveEmployeeRemunerationPage } from '../pages/dashboards/executive/pages/ExecutiveEmployeeRemunerationPage';
import { ExecutiveEmployeeSalaryPage } from '../pages/dashboards/executive/pages/ExecutiveEmployeeSalaryPage';
import { ExecutiveAttendancePage } from '../pages/dashboards/executive/pages/ExecutiveAttendancePage';
import { ExecutivePromotionsPage } from '../pages/dashboards/executive/pages/ExecutivePromotionsPage';
import { ExecutiveCouponsPage } from '../pages/dashboards/executive/pages/ExecutiveCouponsPage';
import { ExecutiveCurrencyRatesPage } from '../pages/dashboards/executive/pages/ExecutiveCurrencyRatesPage';
import { ExecutivePaymentGatewaysPage } from '../pages/dashboards/executive/pages/ExecutivePaymentGatewaysPage';
import { ExecutivePrintersPage } from '../pages/dashboards/executive/pages/ExecutivePrintersPage';
import { ExecutiveStockTransfersPage } from '../pages/dashboards/executive/pages/ExecutiveStockTransfersPage';
import { ExecutiveReceiptsPage } from '../pages/dashboards/executive/pages/ExecutiveReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/executive/components/receipts/Receipt';
import { ExecutiveExpenseCategoriesPage } from '../pages/dashboards/executive/pages/ExecutiveExpenseCategoriesPage';
import { ExecutiveExpensesPage } from '../pages/dashboards/executive/pages/ExecutiveExpensesPage';
import { ExecutiveReorderRulesPage } from '../pages/dashboards/executive/pages/ExecutiveReorderRulesPage';
import { ExecutiveReportExportsPage } from '../pages/dashboards/executive/pages/ExecutiveReportExportsPage';
import { ExecutiveProductAuditsPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditsPage';
import { ExecutiveProductAuditPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditPage';
import { ExecutiveProductAuditReportPage } from '../pages/dashboards/executive/pages/ExecutiveProductAuditReportPage';
import { ExecutiveFinancialAuditsPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditsPage';
import { ExecutiveFinancialAuditPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditPage';
import { ExecutiveFinancialAuditReportPage } from '../pages/dashboards/executive/pages/ExecutiveFinancialAuditReportPage';
import { ExecutiveTaxPage } from '../pages/dashboards/executive/pages/ExecutiveTaxPage';
import { ProcurementRoutes } from './ProcurementRoutes';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { PosPage } from '../pages/dashboards/shared/pos/PosPage';
import { ActivityLogPage } from '../pages/dashboards/shared/activity-log/ActivityLogPage';
import { ProtectedRoutes } from './ProtectedRoutes';

export const BranchManagerRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route element={<ProtectedRoutes />}>
        <Route path='pos' element={<PosPage />} />
        <Route element={<BranchManagerLayout />}>
          <Route index element={<BranchManagerDashboardPage />} />
          <Route path='workers' element={<ExecutiveWorkersPage />} />
          <Route path='suppliers' element={<ExecutiveProductsPage />} />
          <Route path='products' element={<ExecutiveProductsPage />} />
          <Route path='products/:id' element={<Product />} />
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
          <Route path='analytics' element={<ExecutiveAnalyticsPage />} />
          <Route path='reports' element={<ExecutiveReportsPage />} />
          <Route path='finance/transactions' element={<ExecutiveFinanceTransactionsPage />} />
          <Route path='finance/reports' element={<ExecutiveFinanceReportsPage />} />
          <Route path='finance/cashflow' element={<ExecutiveFinanceCashFlowPage />} />
          <Route path='cashflow' element={<ExecutiveFinanceCashFlowPage />} />
          <Route path='attendance' element={<ExecutiveAttendancePage />} />
          <Route path='employee-salaries' element={<ExecutiveEmployeeSalaryPage />} />
          <Route path='remuneration' element={<ExecutiveEmployeeRemunerationPage />} />
          <Route path='activity-log' element={<ActivityLogPage />} />
          <Route path='promotions' element={<ExecutivePromotionsPage />} />
          <Route path='coupons' element={<ExecutiveCouponsPage />} />
          <Route path='messages' element={<ExecutiveMessagesPage />} />
          <Route path='notifications' element={<ExecutiveNotificationsPage />} />
          <Route path='notifications/:id' element={<NotificationDetailPage scope='branch_manager' />} />
          <Route path='currency-rates' element={<ExecutiveCurrencyRatesPage />} />
          <Route path='printers' element={<ExecutivePrintersPage />} />
          <Route path='stock-transfers' element={<ExecutiveStockTransfersPage />} />
          <Route path='reorder-rules' element={<ExecutiveReorderRulesPage />} />
          <Route path='report-exports' element={<ExecutiveReportExportsPage />} />
          <Route path='receipts' element={<ExecutiveReceiptsPage />} />
          <Route path='receipts/:id' element={<ReceiptDetail />} />
          <Route path='expense-categories' element={<ExecutiveExpenseCategoriesPage />} />
          <Route path='expenses' element={<ExecutiveExpensesPage />} />
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
