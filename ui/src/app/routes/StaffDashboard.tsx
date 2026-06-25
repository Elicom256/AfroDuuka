import { Route, Routes } from 'react-router-dom';
import { StaffLayout } from '../pages/dashboards/staff/StaffLayout';
import { StaffDashboardPage } from '../pages/dashboards/staff/pages/StaffDashboardPage';
import { StaffSalesPage } from '../pages/dashboards/staff/pages/StaffSalesPage';
import { StaffProductsPage } from '../pages/dashboards/staff/pages/StaffProductsPage';
import { StaffInventoryPage } from '../pages/dashboards/staff/pages/StaffInventoryPage';
import { StaffSalesOverviewPage } from '../pages/dashboards/staff/pages/StaffSalesOverviewPage';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';

export const StaffDashboard = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route path='staff' element={<StaffLayout />}>
        <Route index element={<StaffDashboardPage />} />
        <Route path='sales' element={<StaffSalesPage />} />
        <Route path='products' element={<StaffProductsPage />} />
        <Route path='inventory' element={<StaffInventoryPage />} />
        <Route path='sales-overview' element={<StaffSalesOverviewPage />} />
      </Route>
      {/* unmatched */}
      {/* <Route path='*' element={<NotFound />} /> */}
    </Routes>
  );
};
