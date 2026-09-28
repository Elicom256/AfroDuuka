import { Route, Routes } from 'react-router-dom';
import { ProcurementLayout } from '../pages/dashboards/procurement/ProcurementLayout';
import { ProcurementOverviewPage } from '../pages/dashboards/procurement/pages/ProcurementOverviewPage';
import { ReorderSuggestionsPage } from '../pages/dashboards/procurement/pages/ReorderSuggestionsPage';
import { PurchaseOrdersPage } from '../pages/dashboards/procurement/pages/PurchaseOrdersPage';
import { ProcurementSuppliersPage } from '../pages/dashboards/procurement/pages/ProcurementSuppliersPage';
import { ProcurementHistoryPage } from '../pages/dashboards/procurement/pages/ProcurementHistoryPage';

export const ProcurementRoutes = () => (
  <Routes>
    <Route path='admin/procurement' element={<ProcurementLayout />}>
      <Route index element={<ProcurementOverviewPage />} />
      <Route path='reorder-suggestions' element={<ReorderSuggestionsPage />} />
      <Route path='purchase-orders' element={<PurchaseOrdersPage />} />
      <Route path='suppliers' element={<ProcurementSuppliersPage />} />
      <Route path='history' element={<ProcurementHistoryPage />} />
    </Route>
  </Routes>
);
