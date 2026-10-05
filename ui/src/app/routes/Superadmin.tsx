import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { ProtectedRoutes } from './ProtectedRoutes';
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
import { TodoList } from '../pages/dashboards/executive/components/todos/TodoList';
import { TodoForm } from '../pages/dashboards/executive/components/todos/TodoForm';

export const SuperadminRoutes = () => (
  <Routes>
    {/*
      This tree had no guard of its own and relied entirely on AppRoutes picking it
      from the role. That coupling is what checked.md P1-18 flagged: the moment the
      role branching is refactored, the platform's businesses, plans and subscriptions
      render for whoever navigated here. Guarding it here makes the requirement local
      to the routes that carry it, like every other dashboard tree.
    */}
    <Route element={<ProtectedRoutes />}>
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
      {/*
          Todos are per-user and the api authorises them for any signed-in user
          (api/routes/users.php, auth:sanctum with no role middleware). Declared in
          every dashboard tree so the page is reachable whatever role is signed in,
          instead of matching the tree's own catch-all and rendering NotFound.
        */}
        <Route path='todos' element={<TodoList />} />
        <Route path='create-todo' element={<TodoForm />} />
        <Route path='*' element={<NotFound />} />
        </Route>
    </Route>
  </Routes>
);
