import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { SuperadminLayout } from '../pages/dashboards/superadmin/SuperadminLayout';
import { SuperadminDashboardPage } from '../pages/dashboards/superadmin/pages/SuperadminDashboardPage';
import { SuperadminPlansPage } from '../pages/dashboards/superadmin/pages/SuperadminPlansPage';
import { SuperadminBusinessesPage } from '../pages/dashboards/superadmin/pages/SuperadminBusinessesPage';
import { SuperadminSubscriptionsPage } from '../pages/dashboards/superadmin/pages/SuperadminSubscriptionsPage';
import { SuperadminSubscriptionShow } from '../pages/dashboards/superadmin/pages/SuperadminSubscriptionShow';
import { SuperadminSubscriptionPaymentsPage } from '../pages/dashboards/superadmin/pages/SuperadminSubscriptionPaymentsPage';
import { SuperadminSubscriptionPaymentShow } from '../pages/dashboards/superadmin/pages/SuperadminSubscriptionPaymentShow';
import { SuperadminSettingsPage } from '../pages/dashboards/superadmin/pages/SuperadminSettingsPage';
import { ExecutivePaymentGatewaysPage } from '../pages/dashboards/executive/pages/ExecutivePaymentGatewaysPage';

export const SuperadminRoutes = () => (
  <Routes>
    <Route element={<SuperadminLayout />}>
      <Route index element={<SuperadminDashboardPage />} />
      <Route path='plans' element={<SuperadminPlansPage />} />
      <Route path='businesses' element={<SuperadminBusinessesPage />} />
      <Route path='subscriptions' element={<SuperadminSubscriptionsPage />} />
      <Route path='subscriptions/:id' element={<SuperadminSubscriptionShow />} />
      <Route path='subscription-payments' element={<SuperadminSubscriptionPaymentsPage />} />
      <Route path='subscription-payments/:id' element={<SuperadminSubscriptionPaymentShow />} />
      <Route path='payment-gateways' element={<ExecutivePaymentGatewaysPage />} />
      <Route path='settings' element={<SuperadminSettingsPage />} />
      <Route path='*' element={<NotFound />} />
    </Route>
  </Routes>
);
