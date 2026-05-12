import { Route, Routes } from 'react-router-dom';
import { Home } from '../pages/public/Home';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';
import { Login } from '../pages/public/Login';
import { SignUp } from '../pages/public/SignUp';
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
  AdminSettingsPage,
} from '../pages/dashboards/admin/pages/admin-placeholder-pages';
import { AddBusinessForm } from '../pages/dashboards/admin/components/AddBusinessForm';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { Product } from '../pages/dashboards/admin/components/products/Product';
import { Sale } from '../pages/dashboards/admin/components/sales/Sale';
import { Purchase } from '../pages/dashboards/admin/components/purchases/Purchase';

export const AppRoutes = () => {
  const { data } = useLoggedinUserQuery();
  // console.log('user here==>', data?.data);

  return (
    <div>
      <Routes>
        <Route path='/' element={<Home />} />
        <Route path='login' element={<Login />} />
        <Route path='signup' element={<SignUp />} />
        <Route path='about' element={<About />} />
        <Route path='documentation' element={<Documentation />} />
        {data && (
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
            <Route path='settings' element={<AdminSettingsPage />} />
            <Route path='products/:id' element={<Product />} />
          </Route>
        )}
      </Routes>
    </div>
  );
};
