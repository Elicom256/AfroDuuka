import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { ProtectedRoutes } from './ProtectedRoutes';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

const NotFound = lazy(() => import('./NotFound').then((m) => ({ default: m.NotFound })));
const ProcurementLayout = lazy(() => import('../pages/dashboards/procurement/ProcurementLayout').then((m) => ({ default: m.ProcurementLayout })));
const ProcurementOverviewPage = lazy(() => import('../pages/dashboards/procurement/pages/ProcurementOverviewPage').then((m) => ({ default: m.ProcurementOverviewPage })));
const ReorderSuggestionsPage = lazy(() => import('../pages/dashboards/procurement/pages/ReorderSuggestionsPage').then((m) => ({ default: m.ReorderSuggestionsPage })));
const PurchaseOrdersPage = lazy(() => import('../pages/dashboards/procurement/pages/PurchaseOrdersPage').then((m) => ({ default: m.PurchaseOrdersPage })));
const ProcurementSuppliersPage = lazy(() => import('../pages/dashboards/procurement/pages/ProcurementSuppliersPage').then((m) => ({ default: m.ProcurementSuppliersPage })));
const ProcurementHistoryPage = lazy(() => import('../pages/dashboards/procurement/pages/ProcurementHistoryPage').then((m) => ({ default: m.ProcurementHistoryPage })));
const TodoList = lazy(() => import('../pages/dashboards/executive/components/todos/TodoList').then((m) => ({ default: m.TodoList })));
const TodoForm = lazy(() => import('../pages/dashboards/executive/components/todos/TodoForm').then((m) => ({ default: m.TodoForm })));

export const ProcurementRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Suspense fallback={<PageLoadingState />}>
      <Routes>
        {/*
          This tree had no guard of its own, same as SuperadminRoutes — it inherited
          protection from AppRoutes choosing it by role. Guarding it locally means the
          requirement lives with the routes that carry it, so refactoring the role
          branching cannot quietly expose purchase orders and suppliers (checked.md P1-18).
        */}
        <Route element={<ProtectedRoutes />}>
          <Route element={<ProcurementLayout />}>
            <Route index element={<ProcurementOverviewPage />} />
            <Route path='reorder-suggestions' element={<ReorderSuggestionsPage />} />
            <Route path='purchase-orders' element={<PurchaseOrdersPage />} />
            <Route path='suppliers' element={<ProcurementSuppliersPage />} />
            <Route path='history' element={<ProcurementHistoryPage />} />
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
};
