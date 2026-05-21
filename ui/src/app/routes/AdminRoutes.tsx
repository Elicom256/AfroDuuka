import { AdminLayout } from '../pages/dashboards/admin/AdminLayout';
import { AdminDashboardPage } from '../pages/dashboards/admin/pages/AdminDashboardPage';
import { AdminWorkersPage } from '../pages/dashboards/admin/pages/AdminWorkersPage';
import { AdminProductsPage } from '../pages/dashboards/admin/pages/AdminProductsPage';
import { ProductCategories } from '../pages/dashboards/admin/components/products/ProductCategories';
import { AdminOrdersPage } from '../pages/dashboards/admin/pages/AdminOrdersPage';
import { AdminSalesPage } from '../pages/dashboards/admin/pages/AdminSalesPage';
import { AdminPurchasesPage } from '../pages/dashboards/admin/pages/AdminPurchasesPage';
import {
  AdminInventoryPage,
  AdminCustomersPage,
  AdminAnalyticsPage,
  AdminReportsPage,
  AdminFinancesPage,
  AdminSuppliersPage,
  AdminPromotionsPage,
  AdminCouponsPage,
  AdminHistoryPage,
} from '../pages/dashboards/admin/pages/admin-placeholder-pages';
import { AdminSettingsPage } from '../pages/dashboards/admin/pages/settings';
import { PaymentSettings } from '../pages/dashboards/admin/components/settings/PaymentSettings';
import { CustomerSettings } from '../pages/dashboards/admin/components/settings/CustomerSettings';
import { SupplierSettings } from '../pages/dashboards/admin/components/settings/SupplierSettings';
import { ReportsSettings } from '../pages/dashboards/admin/components/settings/ReportsSettings';
import { PromotionsSettings } from '../pages/dashboards/admin/components/settings/PromotionsSettings';
import { AttendanceSettings } from '../pages/dashboards/admin/components/settings/AttendanceSettings';
import { AddBusinessForm } from '../pages/dashboards/admin/components/AddBusinessForm';
import { Product } from '../pages/dashboards/admin/components/products/Product';
import { Sale } from '../pages/dashboards/admin/components/sales/Sale';
import { Purchase } from '../pages/dashboards/admin/components/purchases/Purchase';
import { BusinessBranches } from '../pages/dashboards/admin/pages/BusinessBranches';
import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { AdminMessagesPage } from '../pages/dashboards/admin/pages/AdminMessagesPage';
import { AdminNotificationsPage } from '../pages/dashboards/admin/pages/AdminNotificationsPage';

export const AdminRoutes = () => {
  return (
    <Routes>
      <Route path='admin' element={<AdminLayout />}>
        <Route index element={<AdminDashboardPage />} />
        <Route path='workers' element={<AdminWorkersPage />} />
        <Route path='create-business' element={<AddBusinessForm />} />
        <Route path='products' element={<AdminProductsPage />} />
        <Route path='product-categories' element={<ProductCategories />} />
        <Route path='sales' element={<AdminSalesPage />} />
        <Route path='sales/:id' element={<Sale />} />
        <Route path='purchases' element={<AdminPurchasesPage />} />
        <Route path='purchases/:id' element={<Purchase />} />
        <Route path='orders' element={<AdminOrdersPage />} />
        <Route path='inventory' element={<AdminInventoryPage />} />
        <Route path='customers' element={<AdminCustomersPage />} />
        <Route path='analytics' element={<AdminAnalyticsPage />} />
        <Route path='reports' element={<AdminReportsPage />} />
        <Route path='finances' element={<AdminFinancesPage />} />
        <Route path='suppliers' element={<AdminSuppliersPage />} />
        <Route path='promotions' element={<AdminPromotionsPage />} />
        <Route path='coupons' element={<AdminCouponsPage />} />
        <Route path='history' element={<AdminHistoryPage />} />
        <Route path='messages' element={<AdminMessagesPage />} />
        <Route path='notifications' element={<AdminNotificationsPage />} />

        <Route path='settings' element={<AdminSettingsPage />}>
          <Route path='payment-settings' element={<PaymentSettings />} />
          <Route path='customer-settings' element={<CustomerSettings />} />
          <Route path='supplier-settings' element={<SupplierSettings />} />
          <Route path='reports-settings' element={<ReportsSettings />} />
          <Route path='promotions-settings' element={<PromotionsSettings />} />
          <Route path='attendance-settings' element={<AttendanceSettings />} />
        </Route>
        <Route path='products/:id' element={<Product />} />
        <Route path='branches' element={<BusinessBranches />} />
      </Route>
      {/* unmatched */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
