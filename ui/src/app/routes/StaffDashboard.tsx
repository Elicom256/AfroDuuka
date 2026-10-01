import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { ProtectedRoutes } from './ProtectedRoutes';
import { StaffLayout } from '../pages/dashboards/staff/StaffLayout';
import { StaffDashboardPage } from '../pages/dashboards/staff/pages/StaffDashboardPage';
import { StaffSalesPage } from '../pages/dashboards/staff/pages/StaffSalesPage';
import { StaffProductsPage } from '../pages/dashboards/staff/pages/StaffProductsPage';
import { StaffSalesOverviewPage } from '../pages/dashboards/staff/pages/StaffSalesOverviewPage';
import { ExecutiveReceiptsPage } from '../pages/dashboards/executive/pages/ExecutiveReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/executive/components/receipts/Receipt';
import { ActivityLogPage } from '../pages/dashboards/shared/activity-log/ActivityLogPage';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';

export const StaffDashboard = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route element={<ProtectedRoutes />}>
        <Route element={<StaffLayout />}>
          <Route index element={<StaffDashboardPage />} />
          <Route path='sales' element={<StaffSalesPage />} />
          <Route path='products' element={<StaffProductsPage />} />
          <Route path='sales-overview' element={<StaffSalesOverviewPage />} />
          <Route path='receipts' element={<ExecutiveReceiptsPage />} />
          <Route path='receipts/:id' element={<ReceiptDetail />} />
          <Route path='activity-log' element={<ActivityLogPage />} />
          <Route path='*' element={<NotFound />} />
        </Route>
      </Route>
    </Routes>
  );
};
