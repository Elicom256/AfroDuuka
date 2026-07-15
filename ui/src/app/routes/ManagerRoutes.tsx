import { Route, Routes } from 'react-router-dom';
import { ManagerLayout } from '../pages/dashboards/manager/ManagerLayout';
import { ManagerDashboardPage } from '../pages/dashboards/manager/pages/ManagerDashboardPage';
import { ManagerSalesPage } from '../pages/dashboards/manager/pages/ManagerSalesPage';
import { ManagerPurchasesPage } from '../pages/dashboards/manager/pages/ManagerPurchasesPage';

import { ManagerProductsPage } from '../pages/dashboards/manager/pages/ManagerProductsPage';
import { ManagerInventoryPage } from '../pages/dashboards/manager/pages/ManagerInventoryPage';
import { ManagerAnalyticsPage } from '../pages/dashboards/manager/pages/ManagerAnalyticsPage';
import { ManagerWorkersPage } from '../pages/dashboards/manager/pages/ManagerWorkersPage';
import { ManagerCustomersPage } from '../pages/dashboards/manager/pages/ManagerCustomersPage';
import { ManagerSuppliersPage } from '../pages/dashboards/manager/pages/ManagerSuppliersPage';
import { ManagerReportsPage } from '../pages/dashboards/manager/pages/ManagerReportsPage';
import { ManagerFinancesPage } from '../pages/dashboards/manager/pages/ManagerFinancesPage';
import { ManagerNotificationsPage } from '../pages/dashboards/manager/pages/ManagerNotificationsPage';
import { ManagerMessagesPage } from '../pages/dashboards/manager/pages/ManagerMessagesPage';
import { ManagerPromotionsPage } from '../pages/dashboards/manager/pages/ManagerPromotionsPage';
import { ManagerAttendancePage } from '../pages/dashboards/manager/pages/ManagerAttendancePage';
import { ManagerHistoryPage } from '../pages/dashboards/manager/pages/ManagerHistoryPage';
import { Product } from '../pages/dashboards/manager/components/products/Product';
import { Purchase } from '../pages/dashboards/manager/components/purchases/Purchase';
import { Sale } from '../pages/dashboards/manager/components/sales/Sale';
import { SaleReturn } from '../pages/dashboards/manageradmin/components/sale-returns/SaleReturn';
import { PurchaseReturn } from '../pages/dashboards/manageradmin/components/purchase-returns/PurchaseReturn';
import { SaleReturnsPage } from '../pages/dashboards/manageradmin/pages/SaleReturnsPage';
import { PurchaseReturnsPage } from '../pages/dashboards/manageradmin/pages/PurchaseReturnsPage';
import { Worker } from '../pages/dashboards/manager/pages/components/Worker';
import { AdminReceiptsPage } from '../pages/dashboards/admin/pages/AdminReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/admin/components/receipts/Receipt';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const ManagerRoutes = () => {
  const {  isLoading } = useLoggedinUserQuery();
      if (isLoading) {
        return <PageLoadingState />;
      }
  return (
    <Routes>
      <Route path='manager' element={<ManagerLayout />}>
        <Route index element={<ManagerDashboardPage />} />
        <Route path='sales' element={<ManagerSalesPage />} />
        <Route path='sales/:id' element={<Sale />} />
        <Route path='receipts' element={<AdminReceiptsPage />} />
        <Route path='receipts/:id' element={<ReceiptDetail />} />
        <Route path='purchases' element={<ManagerPurchasesPage />} />
        <Route path='purchases/:id' element={<Purchase />} />
        <Route path='sale-returns' element={<SaleReturnsPage />} />
        <Route path='sale-returns/:id' element={<SaleReturn />} />
        <Route path='purchase-returns' element={<PurchaseReturnsPage />} />
        <Route path='purchase-returns/:id' element={<PurchaseReturn />} />
        <Route path='products' element={<ManagerProductsPage />} />
        <Route path='products/:id' element={<Product />} />
        <Route path='inventory' element={<ManagerInventoryPage />} />
        <Route path='analytics' element={<ManagerAnalyticsPage />} />
        <Route path='workers' element={<ManagerWorkersPage />} />
        <Route path='workers/:id' element={<Worker />} />
        <Route path='customers' element={<ManagerCustomersPage />} />
        <Route path='suppliers' element={<ManagerSuppliersPage />} />
        <Route path='reports' element={<ManagerReportsPage />} />
        <Route path='finances' element={<ManagerFinancesPage />} />
        <Route path='notifications' element={<ManagerNotificationsPage />} />
        <Route path='messages' element={<ManagerMessagesPage />} />
        <Route path='promotions' element={<ManagerPromotionsPage />} />
        <Route path='attendance' element={<ManagerAttendancePage />} />
        <Route path='history' element={<ManagerHistoryPage />} />
      </Route>
      {/* unmatched */}
      {/* <Route path='*' element={<NotFound />} /> */}
    </Routes>
  );
};
