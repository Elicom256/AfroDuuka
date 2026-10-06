import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ProtectedRoutes } from './ProtectedRoutes';

const NotFound = lazy(() => import('./NotFound').then((m) => ({ default: m.NotFound })));
const OperationsLayout = lazy(() => import('../pages/dashboards/Operations/OperationsLayout').then((m) => ({ default: m.OperationsLayout })));
const OperationsDashboardPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsDashboardPage').then((m) => ({ default: m.OperationsDashboardPage })));
const OperationsSalesPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsSalesPage').then((m) => ({ default: m.OperationsSalesPage })));
const OperationsPurchasesPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsPurchasesPage').then((m) => ({ default: m.OperationsPurchasesPage })));
const OperationsProductsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsProductsPage').then((m) => ({ default: m.OperationsProductsPage })));
const OperationsInventoryPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsInventoryPage').then((m) => ({ default: m.OperationsInventoryPage })));
const OperationsAnalyticsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsAnalyticsPage').then((m) => ({ default: m.OperationsAnalyticsPage })));
const OperationsWorkersPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsWorkersPage').then((m) => ({ default: m.OperationsWorkersPage })));
const OperationsCustomersPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsCustomersPage').then((m) => ({ default: m.OperationsCustomersPage })));
const OperationsSuppliersPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsSuppliersPage').then((m) => ({ default: m.OperationsSuppliersPage })));
const OperationsReportsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsReportsPage').then((m) => ({ default: m.OperationsReportsPage })));
const OperationsFinancesPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsFinancesPage').then((m) => ({ default: m.OperationsFinancesPage })));
const OperationsNotificationsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsNotificationsPage').then((m) => ({ default: m.OperationsNotificationsPage })));
const NotificationDetailPage = lazy(() => import('../pages/dashboards/shared/notifications/NotificationDetailPage').then((m) => ({ default: m.NotificationDetailPage })));
const OperationsMessagesPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsMessagesPage').then((m) => ({ default: m.OperationsMessagesPage })));
const OperationsPromotionsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsPromotionsPage').then((m) => ({ default: m.OperationsPromotionsPage })));
const OperationsOrdersPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsOrdersPage').then((m) => ({ default: m.OperationsOrdersPage })));
const QuotationsPage = lazy(() => import('../pages/dashboards/shared/quotations/QuotationsPage').then((m) => ({ default: m.QuotationsPage })));
const OperationsAttendancePage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsAttendancePage').then((m) => ({ default: m.OperationsAttendancePage })));
const Product = lazy(() => import('../pages/dashboards/Operations/components/products/Product').then((m) => ({ default: m.Product })));
const Purchase = lazy(() => import('../pages/dashboards/Operations/components/purchases/Purchase').then((m) => ({ default: m.Purchase })));
const Sale = lazy(() => import('../pages/dashboards/Operations/components/sales/Sale').then((m) => ({ default: m.Sale })));
const SaleReturn = lazy(() => import('../pages/dashboards/Operations/components/sale-returns/SaleReturn').then((m) => ({ default: m.SaleReturn })));
const PurchaseReturn = lazy(() => import('../pages/dashboards/Operations/components/purchase-returns/PurchaseReturn').then((m) => ({ default: m.PurchaseReturn })));
const OperationsSaleReturnsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsSaleReturnsPage').then((m) => ({ default: m.OperationsSaleReturnsPage })));
const OperationsPurchaseReturnsPage = lazy(() => import('../pages/dashboards/Operations/pages/OperationsPurchaseReturnsPage').then((m) => ({ default: m.OperationsPurchaseReturnsPage })));
const Worker = lazy(() => import('../pages/dashboards/Operations/pages/components/Worker').then((m) => ({ default: m.Worker })));
const ExecutiveReceiptsPage = lazy(() => import('../pages/dashboards/executive/pages/ExecutiveReceiptsPage').then((m) => ({ default: m.ExecutiveReceiptsPage })));
const ReceiptDetail = lazy(() => import('../pages/dashboards/executive/components/receipts/Receipt').then((m) => ({ default: m.ReceiptDetail })));
const PosPage = lazy(() => import('../pages/dashboards/shared/pos/PosPage').then((m) => ({ default: m.PosPage })));
const ActivityLogPage = lazy(() => import('../pages/dashboards/shared/activity-log/ActivityLogPage').then((m) => ({ default: m.ActivityLogPage })));
const TodoList = lazy(() => import('../pages/dashboards/executive/components/todos/TodoList').then((m) => ({ default: m.TodoList })));
const TodoForm = lazy(() => import('../pages/dashboards/executive/components/todos/TodoForm').then((m) => ({ default: m.TodoForm })));

export const OperationsRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
    <Suspense fallback={<PageLoadingState />}>
      <Routes>
        <Route element={<ProtectedRoutes />}>
          <Route path='pos' element={<PosPage />} />
          <Route element={<OperationsLayout />}>
            <Route index element={<OperationsDashboardPage />} />
            <Route path='sales' element={<OperationsSalesPage />} />
            <Route path='sales/:id' element={<Sale />} />
            <Route path='receipts' element={<ExecutiveReceiptsPage />} />
            <Route path='receipts/:id' element={<ReceiptDetail />} />
            <Route path='purchases' element={<OperationsPurchasesPage />} />
            <Route path='purchases/:id' element={<Purchase />} />
            <Route path='sale-returns' element={<OperationsSaleReturnsPage />} />
            <Route path='sale-returns/:id' element={<SaleReturn />} />
            <Route path='purchase-returns' element={<OperationsPurchaseReturnsPage />} />
            <Route path='purchase-returns/:id' element={<PurchaseReturn />} />
            <Route path='products' element={<OperationsProductsPage />} />
            <Route path='products/:id' element={<Product />} />
            <Route path='inventory' element={<OperationsInventoryPage />} />
            <Route path='analytics' element={<OperationsAnalyticsPage />} />
            <Route path='workers' element={<OperationsWorkersPage />} />
            <Route path='workers/:id' element={<Worker />} />
            <Route path='customers' element={<OperationsCustomersPage />} />
            <Route path='suppliers' element={<OperationsSuppliersPage />} />
            <Route path='reports' element={<OperationsReportsPage />} />
            <Route path='finances' element={<OperationsFinancesPage />} />
            <Route path='finance' element={<OperationsFinancesPage />} />
            <Route path='notifications' element={<OperationsNotificationsPage />} />
            <Route path='notifications/:id' element={<NotificationDetailPage scope='operations' />} />
            <Route path='messages' element={<OperationsMessagesPage />} />
            <Route path='orders' element={<OperationsOrdersPage />} />
            <Route path='quotations' element={<QuotationsPage />} />
            <Route path='promotions' element={<OperationsPromotionsPage />} />
            <Route path='attendance' element={<OperationsAttendancePage />} />
            <Route path='activity-log' element={<ActivityLogPage />} />
            {/*
              Todos are per-user and the api authorises them for any signed-in user
              (api/routes/users.php, auth:sanctum with no role middleware). Declared
              in every dashboard tree so the page is reachable whatever role is signed
              in, instead of matching the tree's own catch-all and rendering NotFound.
            */}
            <Route path='todos' element={<TodoList />} />
            <Route path='create-todo' element={<TodoForm />} />
          </Route>
          <Route path='*' element={<NotFound />} />
        </Route>
      </Routes>
    </Suspense>
  );
};

