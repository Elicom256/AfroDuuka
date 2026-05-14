import { Route, Routes } from 'react-router-dom';
import { ManagerLayout } from '../pages/dashboards/manager/ManagerLayout';
import { ManagerDashboardPage } from '../pages/dashboards/manager/pages/ManagerDashboardPage';
import { ManagerSalesPage } from '../pages/dashboards/manager/pages/ManagerSalesPage';
import { ManagerPurchasesPage } from '../pages/dashboards/manager/pages/ManagerPurchasesPage';
import { ManagerProductsPage } from '../pages/dashboards/manager/pages/ManagerProductsPage';
import { ManagerInventoryPage } from '../pages/dashboards/manager/pages/ManagerInventoryPage';
import { ManagerAnalyticsPage } from '../pages/dashboards/manager/pages/ManagerAnalyticsPage';
import { NotFound } from './NotFound';

export const ManagerRoutes = () => {
  return (
    <Routes>
      <Route path='manager' element={<ManagerLayout />}>
        <Route index element={<ManagerDashboardPage />} />
        <Route path='sales' element={<ManagerSalesPage />} />
        <Route path='purchases' element={<ManagerPurchasesPage />} />
        <Route path='products' element={<ManagerProductsPage />} />
        <Route path='inventory' element={<ManagerInventoryPage />} />
        <Route path='analytics' element={<ManagerAnalyticsPage />} /> 
      </Route>
      {/* unmatched */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
