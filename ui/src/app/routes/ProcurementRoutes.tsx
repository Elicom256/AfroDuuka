import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { ProtectedRoutes } from './ProtectedRoutes';
import { ProcurementLayout } from '../pages/dashboards/procurement/ProcurementLayout';
import { ProcurementOverviewPage } from '../pages/dashboards/procurement/pages/ProcurementOverviewPage';
import { ReorderSuggestionsPage } from '../pages/dashboards/procurement/pages/ReorderSuggestionsPage';
import { PurchaseOrdersPage } from '../pages/dashboards/procurement/pages/PurchaseOrdersPage';
import { ProcurementSuppliersPage } from '../pages/dashboards/procurement/pages/ProcurementSuppliersPage';
import { ProcurementHistoryPage } from '../pages/dashboards/procurement/pages/ProcurementHistoryPage';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { TodoList } from '../pages/dashboards/executive/components/todos/TodoList';
import { TodoForm } from '../pages/dashboards/executive/components/todos/TodoForm';

export const ProcurementRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
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
  );
};
