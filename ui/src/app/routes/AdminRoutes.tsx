import { AdminLayout } from '../pages/dashboards/admin/AdminLayout';
import { AdminDashboardPage } from '../pages/dashboards/admin/pages/AdminDashboardPage';
import { AdminWorkersPage } from '../pages/dashboards/admin/pages/AdminWorkersPage';
import { AdminProductsPage } from '../pages/dashboards/admin/pages/AdminProductsPage';
import { AdminOrdersPage } from '../pages/dashboards/admin/pages/AdminOrdersPage';
import { AdminSalesPage } from '../pages/dashboards/admin/pages/AdminSalesPage';
import { AdminPurchasesPage } from '../pages/dashboards/admin/pages/AdminPurchasesPage';
import { AdminSettingsPage } from '../pages/dashboards/admin/pages/settings';
import { PaymentSettings } from '../pages/dashboards/admin/components/settings/PaymentSettings';
import { CustomerSettings } from '../pages/dashboards/admin/components/settings/CustomerSettings';
import { ReportsSettings } from '../pages/dashboards/admin/components/settings/ReportsSettings';
import { PromotionsSettings } from '../pages/dashboards/admin/components/settings/PromotionsSettings';
import { AttendanceSettings } from '../pages/dashboards/admin/components/settings/AttendanceSettings';
import { AddBusinessForm } from '../pages/dashboards/admin/components/AddBusinessForm';
import { Product } from '../pages/dashboards/admin/components/products/Product';
import { Sale } from '../pages/dashboards/admin/components/sales/Sale';
import { Purchase } from '../pages/dashboards/admin/components/purchases/Purchase';
import { SaleReturn } from '../pages/dashboards/manageradmin/components/sale-returns/SaleReturn';
import { PurchaseReturn } from '../pages/dashboards/manageradmin/components/purchase-returns/PurchaseReturn';
import { SaleReturnsPage } from '../pages/dashboards/manageradmin/pages/SaleReturnsPage';
import { PurchaseReturnsPage } from '../pages/dashboards/manageradmin/pages/PurchaseReturnsPage';
import { BusinessBranches } from '../pages/dashboards/admin/pages/BusinessBranches';
import { Route, Routes } from 'react-router-dom';
import { AdminMessagesPage } from '../pages/dashboards/admin/pages/AdminMessagesPage';
import { AdminNotificationsPage } from '../pages/dashboards/admin/pages/AdminNotificationsPage';
import { ProductCategories } from '../pages/dashboards/admin/components/products/ProductCategories';
import { ProductCategory } from '../pages/dashboards/admin/components/products/ProductCategory';
import { ProtectedRoutes } from './ProtectedRoutes';
import { AdminSuppliersPage } from '../pages/dashboards/admin/pages/AdminSuppliersPage';
import { AdminInventoryPage } from '../pages/dashboards/admin/pages/AdminInventoryPage';
import { AdminCustomersPage } from '../pages/dashboards/admin/pages/AdminCustomersPage';
import { AdminAnalyticsPage } from '../pages/dashboards/admin/pages/AdminAnalyticsPage';
import { AdminReportsPage } from '../pages/dashboards/admin/pages/AdminReportsPage';
import { AdminFinancesPage } from '../pages/dashboards/admin/pages/AdminFinancesPage';
import { AdminTaxesPage } from '../pages/dashboards/admin/pages/AdminTaxesPage';
import { AdminEmployeeRemunerationPage } from '../pages/dashboards/admin/pages/AdminEmployeeRemunerationPage';
import { AdminEmployeeSalaryPage } from '../pages/dashboards/admin/pages/AdminEmployeeSalaryPage';
import { AdminAttendancePage } from '../pages/dashboards/admin/pages/AdminAttendancePage';
import { AdminBusinessActivityLogs } from '../pages/dashboards/admin/pages/AdminBusinessActivityLogs';
import { AdminPromotionsPage } from '../pages/dashboards/admin/pages/AdminPromotionsPage';
import { AdminCouponsPage } from '../pages/dashboards/admin/pages/AdminCouponsPage';
import { AdminHistoryPage } from '../pages/dashboards/admin/pages/AdminHistoryPage';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { TaxesObligatedTo } from '../pages/dashboards/admin/components/taxes/TaxesObligatedTo';
import { Attendance } from '../pages/dashboards/admin/components/attendance/Attendance';
import { Worker } from '../pages/dashboards/admin/components/workers/Worker';
import { TaxObligatedTo } from '../pages/dashboards/admin/components/taxes/TaxObligatedTo';
import { Supplier } from '../pages/dashboards/admin/components/suppliers/Supplier';
import { Customer } from '../pages/dashboards/admin/components/customers/Customer';
import { TodoList } from '../pages/dashboards/admin/components/todos/TodoList';
import { TodoForm } from '../pages/dashboards/admin/components/todos/TodoForm';
import SupplierSettings from '../pages/dashboards/admin/components/settings/SupplierSettings';
import { CurrencySettings } from '../pages/dashboards/admin/components/settings/CurrencySettings';
import { BusinessInfoSettings } from '../pages/dashboards/admin/components/settings/BusinessInfoSettings';
import { PlanBillingSettings } from '../pages/dashboards/admin/components/settings/PlanBillingSettings';
import { AdminCurrencyRatesPage } from '../pages/dashboards/admin/pages/AdminCurrencyRatesPage';
import { AdminPaymentGatewaysPage } from '../pages/dashboards/admin/pages/AdminPaymentGatewaysPage';
import { AdminPrintersPage } from '../pages/dashboards/admin/pages/AdminPrintersPage';
import { AdminStockTransfersPage } from '../pages/dashboards/admin/pages/AdminStockTransfersPage';
import { AdminReceiptsPage } from '../pages/dashboards/admin/pages/AdminReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/admin/components/receipts/Receipt';
import { AdminExpenseCategoriesPage } from '../pages/dashboards/admin/pages/AdminExpenseCategoriesPage';
import { AdminExpensesPage } from '../pages/dashboards/admin/pages/AdminExpensesPage';
import { AdminReorderRulesPage } from '../pages/dashboards/admin/pages/AdminReorderRulesPage';
import { AdminTaxInvoicesPage } from '../pages/dashboards/admin/pages/AdminTaxInvoicesPage';
import { AdminLoyaltyPage } from '../pages/dashboards/admin/pages/AdminLoyaltyPage';
import { AdminSubscriptionPaymentsPage } from '../pages/dashboards/admin/pages/AdminSubscriptionPaymentsPage';
import { AdminReportExportsPage } from '../pages/dashboards/admin/pages/AdminReportExportsPage';

export const AdminRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route element={<ProtectedRoutes />}>
        <Route path='admin' element={<AdminLayout />}>
          <Route index element={<AdminDashboardPage />} />

          <Route path='workers' element={<AdminWorkersPage />} />
          <Route path='workers/:id' element={<Worker />} />
          <Route path='suppliers' element={<AdminSuppliersPage />} />
          <Route path='suppliers/:id' element={<Supplier />} />

          <Route path='create-business' element={<AddBusinessForm />} />
          <Route path='products' element={<AdminProductsPage />} />
          <Route path='product-categories' element={<ProductCategories />} />
          <Route path='product-categories/:id' element={<ProductCategory />} />
          <Route path='sales' element={<AdminSalesPage />} />
          <Route path='sales/:id' element={<Sale />} />
          <Route path='sale-returns' element={<SaleReturnsPage />} />
          <Route path='sale-returns/:id' element={<SaleReturn />} />
          <Route path='purchases' element={<AdminPurchasesPage />} />
          <Route path='purchases/:id' element={<Purchase />} />
          <Route path='purchase-returns' element={<PurchaseReturnsPage />} />
          <Route path='purchase-returns/:id' element={<PurchaseReturn />} />
          <Route path='orders' element={<AdminOrdersPage />} />
          <Route path='inventory' element={<AdminInventoryPage />} />
          <Route path='customers' element={<AdminCustomersPage />} />
          <Route path='customers/:id' element={<Customer />} />

          <Route path='analytics' element={<AdminAnalyticsPage />} />
          <Route path='todos' element={<TodoList />} />
          <Route path='create-todo' element={<TodoForm />} />
          <Route path='reports' element={<AdminReportsPage />} />
          <Route path='finances' element={<AdminFinancesPage />} />
          <Route path='attendance' element={<AdminAttendancePage />} />
          <Route path='attendance/:id' element={<Attendance />} />
          <Route path='employee-salaries' element={<AdminEmployeeSalaryPage />} />
          <Route path='taxes' element={<AdminTaxesPage />} />
          <Route path='obligated-taxes' element={<TaxesObligatedTo />} />
          <Route path='obligated-taxes/:id' element={<TaxObligatedTo />} />
          <Route path='remuneration' element={<AdminEmployeeRemunerationPage />} />
          <Route path='activity-logs' element={<AdminBusinessActivityLogs />} />
          <Route path='promotions' element={<AdminPromotionsPage />} />
          <Route path='coupons' element={<AdminCouponsPage />} />
          <Route path='history' element={<AdminHistoryPage />} />
          <Route path='messages' element={<AdminMessagesPage />} />
          <Route path='notifications' element={<AdminNotificationsPage />} />

          <Route path='settings' element={<AdminSettingsPage />}>
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
          </Route>
          <Route path='currency-rates' element={<AdminCurrencyRatesPage />} />
          <Route path='printers' element={<AdminPrintersPage />} />
          <Route path='stock-transfers' element={<AdminStockTransfersPage />} />
          <Route path='reorder-rules' element={<AdminReorderRulesPage />} />
          <Route path='tax-invoices' element={<AdminTaxInvoicesPage />} />
          <Route path='loyalty' element={<AdminLoyaltyPage />} />
          <Route path='report-exports' element={<AdminReportExportsPage />} />
          <Route path='products/:id' element={<Product />} />
          <Route path='receipts' element={<AdminReceiptsPage />} />
          <Route path='receipts/:id' element={<ReceiptDetail />} />
          <Route path='expense-categories' element={<AdminExpenseCategoriesPage />} />
          <Route path='expenses' element={<AdminExpensesPage />} />
          <Route path='branches' element={<BusinessBranches />} />
          <Route path='subscriptions' element={<AdminSubscriptionPaymentsPage />} />
        </Route>
      </Route>
       
      {/* <Route path='*' element={<NotFound />} /> */}
    </Routes>
  );
};
