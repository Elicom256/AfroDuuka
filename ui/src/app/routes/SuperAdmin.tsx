import { Route, Routes } from 'react-router-dom';
import { SuperAdminLayout } from '../pages/dashboards/superadmin/SuperAdminLayout';
import { SuperAdminDashboardPage } from '../pages/dashboards/superadmin/pages/SuperAdminDashboardPage';
import { SuperAdminPlansPage } from '../pages/dashboards/superadmin/pages/SuperAdminPlansPage';
import { SuperAdminBusinessesPage } from '../pages/dashboards/superadmin/pages/SuperAdminBusinessesPage';
import { SuperAdminSubscriptionsPage } from '../pages/dashboards/superadmin/pages/SuperAdminSubscriptionsPage';
import { SuperAdminSubscriptionShow } from '../pages/dashboards/superadmin/pages/SuperAdminSubscriptionShow';
import { SuperAdminSubscriptionPaymentsPage } from '../pages/dashboards/superadmin/pages/SuperAdminSubscriptionPaymentsPage';
import { SuperAdminSubscriptionPaymentShow } from '../pages/dashboards/superadmin/pages/SuperAdminSubscriptionPaymentShow';
import { SuperAdminSettingsPage } from '../pages/dashboards/superadmin/pages/SuperAdminSettingsPage';
import { AdminPaymentGatewaysPage } from '../pages/dashboards/admin/pages/AdminPaymentGatewaysPage';

export const SuperAdminRoutes = () => (
  <Routes>
    <Route path='superadmin' element={<SuperAdminLayout />}>
      <Route index element={<SuperAdminDashboardPage />} />
      <Route path='plans' element={<SuperAdminPlansPage />} />
      <Route path='businesses' element={<SuperAdminBusinessesPage />} />
      <Route path='subscriptions' element={<SuperAdminSubscriptionsPage />} />
      <Route path='subscriptions/:id' element={<SuperAdminSubscriptionShow />} />
      <Route path='subscription-payments' element={<SuperAdminSubscriptionPaymentsPage />} />
      <Route path='subscription-payments/:id' element={<SuperAdminSubscriptionPaymentShow />} />
      <Route path='payment-gateways' element={<AdminPaymentGatewaysPage />} />
      <Route path='settings' element={<SuperAdminSettingsPage />} />
    </Route>
  </Routes>
);
