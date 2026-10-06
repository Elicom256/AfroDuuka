import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { ProtectedRoutes } from './ProtectedRoutes';
import { PageLoadingState } from '@/utils/PageLoadingState';

const NotFound = lazy(() => import('./NotFound').then((m) => ({ default: m.NotFound })));
const SuperadminLayout = lazy(() => import('../pages/dashboards/superadmin/SuperadminLayout').then((m) => ({ default: m.SuperadminLayout })));
const SuperadminDashboardPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminDashboardPage').then((m) => ({ default: m.SuperadminDashboardPage })));
const SuperadminPlansPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminPlansPage').then((m) => ({ default: m.SuperadminPlansPage })));
const SuperadminBusinessesPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminBusinessesPage').then((m) => ({ default: m.SuperadminBusinessesPage })));
const SuperadminSubscriptionsPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminSubscriptionsPage').then((m) => ({ default: m.SuperadminSubscriptionsPage })));
const SuperadminSubscriptionShow = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminSubscriptionShow').then((m) => ({ default: m.SuperadminSubscriptionShow })));
const SuperadminSubscriptionPaymentsPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminSubscriptionPaymentsPage').then((m) => ({ default: m.SuperadminSubscriptionPaymentsPage })));
const SuperadminSubscriptionPaymentShow = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminSubscriptionPaymentShow').then((m) => ({ default: m.SuperadminSubscriptionPaymentShow })));
const SuperadminSettingsPage = lazy(() => import('../pages/dashboards/superadmin/pages/SuperadminSettingsPage').then((m) => ({ default: m.SuperadminSettingsPage })));
const ExecutivePaymentGatewaysPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutivePaymentGatewaysPage').then((m) => ({ default: m.ExecutivePaymentGatewaysPage })));
const TodoList = lazy(() => import('../pages/dashboards/executive/components/todos/TodoList').then((m) => ({ default: m.TodoList })));
const TodoForm = lazy(() => import('../pages/dashboards/executive/components/todos/TodoForm').then((m) => ({ default: m.TodoForm })));

export const SuperadminRoutes = () => (
  <Suspense fallback={<PageLoadingState />}>
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
  </Suspense>
);
