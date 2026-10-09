import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ProtectedRoutes } from './ProtectedRoutes';

const NotFound = lazy(() => import('./NotFound').then((m) => ({ default: m.NotFound })));
const BranchManagerLayout = lazy(() => import('../pages/dashboards/branch-manager/BranchManagerLayout').then((m) => ({ default: m.BranchManagerLayout })));
const BranchManagerDashboardPage = lazy(() => import('../pages/dashboards/branch-manager/BranchManagerDashboardPage').then((m) => ({ default: m.BranchManagerDashboardPage })));
const ExecutiveWorkersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveWorkersPage').then((m) => ({ default: m.ExecutiveWorkersPage })));
const ExecutiveProductsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveProductsPage').then((m) => ({ default: m.ExecutiveProductsPage })));
const ExecutiveSuppliersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveSuppliersPage').then((m) => ({ default: m.ExecutiveSuppliersPage })));
const Supplier = lazy(() => import('../pages/dashboards/executive/components/suppliers/Supplier').then((m) => ({ default: m.Supplier })));
const Customer = lazy(() => import('../pages/dashboards/executive/components/customers/Customer').then((m) => ({ default: m.Customer })));
const ExecutiveOrdersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveOrdersPage').then((m) => ({ default: m.ExecutiveOrdersPage })));
const QuotationsPage = lazy(() => import('../pages/dashboards/shared/quotations/QuotationsPage').then((m) => ({ default: m.QuotationsPage })));
const ExecutiveSalesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveSalesPage').then((m) => ({ default: m.ExecutiveSalesPage })));
const ExecutivePurchasesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutivePurchasesPage').then((m) => ({ default: m.ExecutivePurchasesPage })));
const Product = lazy(() => import('../pages/dashboards/executive/components/products/Product').then((m) => ({ default: m.Product })));
const ProductCategories = lazy(() => import('../pages/dashboards/executive/components/products/ProductCategories').then((m) => ({ default: m.ProductCategories })));
const ProductCategory = lazy(() => import('../pages/dashboards/executive/components/products/ProductCategory').then((m) => ({ default: m.ProductCategory })));
const Sale = lazy(() => import('../pages/dashboards/executive/components/sales/Sale').then((m) => ({ default: m.Sale })));
const Purchase = lazy(() => import('../pages/dashboards/executive/components/purchases/Purchase').then((m) => ({ default: m.Purchase })));
const SaleReturn = lazy(() => import('../pages/dashboards/executive/components/sale-returns/SaleReturn').then((m) => ({ default: m.SaleReturn })));
const PurchaseReturn = lazy(() => import('../pages/dashboards/executive/components/purchase-returns/PurchaseReturn').then((m) => ({ default: m.PurchaseReturn })));
const ExecutiveSaleReturnsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveSaleReturnsPage').then((m) => ({ default: m.ExecutiveSaleReturnsPage })));
const ExecutivePurchaseReturnsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutivePurchaseReturnsPage').then((m) => ({ default: m.ExecutivePurchaseReturnsPage })));
const ExecutiveMessagesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveMessagesPage').then((m) => ({ default: m.ExecutiveMessagesPage })));
const ExecutiveNotificationsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveNotificationsPage').then((m) => ({ default: m.ExecutiveNotificationsPage })));
const NotificationDetailPage = lazy(() => import('../pages/dashboards/shared/notifications/NotificationDetailPage').then((m) => ({ default: m.NotificationDetailPage })));
const ExecutiveCustomersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveCustomersPage').then((m) => ({ default: m.ExecutiveCustomersPage })));
const ExecutiveAnalyticsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveAnalyticsPage').then((m) => ({ default: m.ExecutiveAnalyticsPage })));
const ExecutiveReportsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveReportsPage').then((m) => ({ default: m.ExecutiveReportsPage })));
const ExecutiveFinanceTransactionsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinanceTransactionsPage').then((m) => ({ default: m.ExecutiveFinanceTransactionsPage })));
const ExecutiveFinanceTransactionPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinanceTransactionPage').then((m) => ({ default: m.ExecutiveFinanceTransactionPage })));
const ExecutiveFinanceReportsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinanceReportsPage').then((m) => ({ default: m.ExecutiveFinanceReportsPage })));
const ExecutiveFinanceCashFlowPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinanceCashFlowPage').then((m) => ({ default: m.ExecutiveFinanceCashFlowPage })));
const ExecutiveEmployeeRemunerationPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveEmployeeRemunerationPage').then((m) => ({ default: m.ExecutiveEmployeeRemunerationPage })));
const ExecutiveEmployeeSalaryPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveEmployeeSalaryPage').then((m) => ({ default: m.ExecutiveEmployeeSalaryPage })));
const ExecutiveAttendancePage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveAttendancePage').then((m) => ({ default: m.ExecutiveAttendancePage })));
const ExecutivePromotionsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutivePromotionsPage').then((m) => ({ default: m.ExecutivePromotionsPage })));
const ExecutiveCouponsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveCouponsPage').then((m) => ({ default: m.ExecutiveCouponsPage })));
const ExecutiveCurrencyRatesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveCurrencyRatesPage').then((m) => ({ default: m.ExecutiveCurrencyRatesPage })));
const ExecutivePrintersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutivePrintersPage').then((m) => ({ default: m.ExecutivePrintersPage })));
const ExecutiveStockTransfersPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveStockTransfersPage').then((m) => ({ default: m.ExecutiveStockTransfersPage })));
const ExecutiveReceiptsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveReceiptsPage').then((m) => ({ default: m.ExecutiveReceiptsPage })));
const ReceiptDetail = lazy(() => import('../pages/dashboards/executive/components/receipts/Receipt').then((m) => ({ default: m.ReceiptDetail })));
const ExecutiveExpenseCategoriesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveExpenseCategoriesPage').then((m) => ({ default: m.ExecutiveExpenseCategoriesPage })));
const ExecutiveExpensesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveExpensesPage').then((m) => ({ default: m.ExecutiveExpensesPage })));
const ExecutiveReorderRulesPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveReorderRulesPage').then((m) => ({ default: m.ExecutiveReorderRulesPage })));
const ExecutiveReportExportsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveReportExportsPage').then((m) => ({ default: m.ExecutiveReportExportsPage })));
const ExecutiveProductAuditsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveProductAuditsPage').then((m) => ({ default: m.ExecutiveProductAuditsPage })));
const ExecutiveProductAuditPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveProductAuditPage').then((m) => ({ default: m.ExecutiveProductAuditPage })));
const ExecutiveProductAuditReportPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveProductAuditReportPage').then((m) => ({ default: m.ExecutiveProductAuditReportPage })));
const ExecutiveFinancialAuditsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinancialAuditsPage').then((m) => ({ default: m.ExecutiveFinancialAuditsPage })));
const ExecutiveFinancialAuditPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinancialAuditPage').then((m) => ({ default: m.ExecutiveFinancialAuditPage })));
const ExecutiveFinancialAuditReportPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveFinancialAuditReportPage').then((m) => ({ default: m.ExecutiveFinancialAuditReportPage })));
const ExecutiveTaxPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveTaxPage').then((m) => ({ default: m.ExecutiveTaxPage })));
const ProcurementRoutes = lazy(() => import('./ProcurementRoutes').then((m) => ({ default: m.ProcurementRoutes })));
const TodoList = lazy(() => import('../pages/dashboards/executive/components/todos/TodoList').then((m) => ({ default: m.TodoList })));
const TodoForm = lazy(() => import('../pages/dashboards/executive/components/todos/TodoForm').then((m) => ({ default: m.TodoForm })));
const PosPage = lazy(() => import('../pages/dashboards/shared/pos/PosPage').then((m) => ({ default: m.PosPage })));
const ActivityLogPage = lazy(() => import('../pages/dashboards/shared/activity-log/ActivityLogPage').then((m) => ({ default: m.ActivityLogPage })));

export const BranchManagerRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Suspense fallback={<PageLoadingState />}>
      <Routes>
        <Route element={<ProtectedRoutes />}>
          <Route path='pos' element={<PosPage />} />
          <Route element={<BranchManagerLayout />}>
            <Route index element={<BranchManagerDashboardPage />} />
            <Route path='workers' element={<ExecutiveWorkersPage />} />
            <Route path='suppliers' element={<ExecutiveSuppliersPage />} />
            <Route path='suppliers/:id' element={<Supplier />} />
            <Route path='products' element={<ExecutiveProductsPage />} />
            <Route path='products/:id' element={<Product />} />
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
            <Route path='reports' element={<ExecutiveReportsPage />} />
            <Route path='finance/transactions' element={<ExecutiveFinanceTransactionsPage />} />
            <Route path='finance/transactions/:id' element={<ExecutiveFinanceTransactionPage />} />
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
            {/*
              Todos are per-user and the api authorises them for any signed-in user
              (api/routes/users.php, auth:sanctum with no role middleware), and
              AuthorizationPolicyCoverageTest asserts a BranchManager may use one. The
              sidebar has advertised this page since it was added, so without the route
              here a BranchManager who clicked "Tasks -> Todos" landed on NotFound.
            */}
            <Route path='todos' element={<TodoList />} />
            <Route path='create-todo' element={<TodoForm />} />
            <Route path='procurement/*' element={<ProcurementRoutes />} />
          </Route>
          <Route path='*' element={<NotFound />} />
        </Route>
      </Routes>
    </Suspense>
  );
};
