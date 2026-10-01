import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { ProcurementLayout } from '../pages/dashboards/procurement/ProcurementLayout';
import { ProcurementOverviewPage } from '../pages/dashboards/procurement/pages/ProcurementOverviewPage';
import { ReorderSuggestionsPage } from '../pages/dashboards/procurement/pages/ReorderSuggestionsPage';
import { PurchaseOrdersPage } from '../pages/dashboards/procurement/pages/PurchaseOrdersPage';
import { ProcurementSuppliersPage } from '../pages/dashboards/procurement/pages/ProcurementSuppliersPage';
import { ProcurementHistoryPage } from '../pages/dashboards/procurement/pages/ProcurementHistoryPage';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const ProcurementRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Routes>
      <Route element={<ProcurementLayout />}>
        <Route index element={<ProcurementOverviewPage />} />
        <Route path='reorder-suggestions' element={<ReorderSuggestionsPage />} />
        <Route path='purchase-orders' element={<PurchaseOrdersPage />} />
        <Route path='suppliers' element={<ProcurementSuppliersPage />} />
        <Route path='history' element={<ProcurementHistoryPage />} />
        <Route path='*' element={<NotFound />} />
      </Route>
    </Routes>
  );
};
