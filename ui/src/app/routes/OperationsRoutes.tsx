import { Route, Routes } from 'react-router-dom';
import { NotFound } from './NotFound';
import { OperationsLayout } from '../pages/dashboards/Operations/OperationsLayout';
import { OperationsDashboardPage } from '../pages/dashboards/Operations/pages/OperationsDashboardPage';
import { OperationsSalesPage } from '../pages/dashboards/Operations/pages/OperationsSalesPage';
import { OperationsPurchasesPage } from '../pages/dashboards/Operations/pages/OperationsPurchasesPage';
import { OperationsProductsPage } from '../pages/dashboards/Operations/pages/OperationsProductsPage';
import { OperationsInventoryPage } from '../pages/dashboards/Operations/pages/OperationsInventoryPage';
import { OperationsAnalyticsPage } from '../pages/dashboards/Operations/pages/OperationsAnalyticsPage';
import { OperationsWorkersPage } from '../pages/dashboards/Operations/pages/OperationsWorkersPage';
import { OperationsCustomersPage } from '../pages/dashboards/Operations/pages/OperationsCustomersPage';
import { OperationsSuppliersPage } from '../pages/dashboards/Operations/pages/OperationsSuppliersPage';
import { OperationsReportsPage } from '../pages/dashboards/Operations/pages/OperationsReportsPage';
import { OperationsFinancesPage } from '../pages/dashboards/Operations/pages/OperationsFinancesPage';
import { OperationsNotificationsPage } from '../pages/dashboards/Operations/pages/OperationsNotificationsPage';
import { NotificationDetailPage } from '../pages/dashboards/shared/notifications/NotificationDetailPage';
import { OperationsMessagesPage } from '../pages/dashboards/Operations/pages/OperationsMessagesPage';
import { OperationsPromotionsPage } from '../pages/dashboards/Operations/pages/OperationsPromotionsPage';
import { OperationsOrdersPage } from '../pages/dashboards/Operations/pages/OperationsOrdersPage';
import { QuotationsPage } from '../pages/dashboards/shared/quotations/QuotationsPage';
import { OperationsAttendancePage } from '../pages/dashboards/Operations/pages/OperationsAttendancePage';
import { Product } from '../pages/dashboards/Operations/components/products/Product';
import { Purchase } from '../pages/dashboards/Operations/components/purchases/Purchase';
import { Sale } from '../pages/dashboards/Operations/components/sales/Sale';
import { SaleReturn } from '../pages/dashboards/Operations/components/sale-returns/SaleReturn';
import { PurchaseReturn } from '../pages/dashboards/Operations/components/purchase-returns/PurchaseReturn';
import { OperationsSaleReturnsPage } from '../pages/dashboards/Operations/pages/OperationsSaleReturnsPage';
import { OperationsPurchaseReturnsPage } from '../pages/dashboards/Operations/pages/OperationsPurchaseReturnsPage';
import { Worker } from '../pages/dashboards/Operations/pages/components/Worker';
import { ExecutiveReceiptsPage } from '../pages/dashboards/executive/pages/ExecutiveReceiptsPage';
import { ReceiptDetail } from '../pages/dashboards/executive/components/receipts/Receipt';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { PosPage } from '../pages/dashboards/shared/pos/PosPage';
import { ActivityLogPage } from '../pages/dashboards/shared/activity-log/ActivityLogPage';
import { ProtectedRoutes } from './ProtectedRoutes';
import { TodoList } from '../pages/dashboards/executive/components/todos/TodoList';
import { TodoForm } from '../pages/dashboards/executive/components/todos/TodoForm';

export const OperationsRoutes = () => {
  const { isLoading } = useLoggedinUserQuery();
  if (isLoading) {
    return <PageLoadingState />;
  }
  return (
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
  );
};

