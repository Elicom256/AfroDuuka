export type DashboardScope = 'executive' | 'branch_manager' | 'operations';

export const NOTIFICATION_TYPE_LABELS: Record<string, string> = {
  low_stock: 'Stock Alert',
  new_sale: 'Sale',
  new_purchase: 'Purchase',
  payment_received: 'Payment Received',
  overdue_payment: 'Overdue Payment',
  attendance: 'Attendance',
};

export const notificationTypeLabel = (type: string): string => NOTIFICATION_TYPE_LABELS[type] ?? type.replace('_', ' ');

export interface NotificationLike {
  type: string;
  data?: Record<string, unknown> | null;
  notifiable_id?: number | string | null;
}

const DASHBOARD_PREFIX = '/dashboard';

const RELATED_MODULE: Record<string, string> = {
  low_stock: 'products',
  new_sale: 'sales',
  new_purchase: 'purchases',
  payment_received: 'sales',
  overdue_payment: 'customers',
  attendance: 'attendance',
};

const RELATED_ID_KEY: Record<string, string> = {
  low_stock: 'product_id',
  new_sale: 'sale_id',
  new_purchase: 'purchase_id',
  overdue_payment: 'customer_id',
};

const DETAIL_ROUTES: Record<DashboardScope, Set<string>> = {
  executive: new Set(['products', 'sales', 'purchases', 'customers', 'attendance']),
  branch_manager: new Set(['products', 'sales', 'purchases', 'customers']),
  operations: new Set(['products', 'sales', 'purchases']),
};

export const NOTIFICATIONS_ROUTE = `${DASHBOARD_PREFIX}/notifications`;

export const notificationRouteForType = (
  notification: NotificationLike | string,
  scope: DashboardScope
): string | null => {
  const type = typeof notification === 'string' ? notification : notification.type;
  const module = RELATED_MODULE[type];
  if (!module) return null;

  if (DETAIL_ROUTES[scope].has(module)) {
    const idKey = RELATED_ID_KEY[type];
    const relatedId =
      idKey && typeof notification === 'object'
        ? notification.data?.[idKey] ?? notification.notifiable_id
        : undefined;
    if (relatedId != null) {
      return `${DASHBOARD_PREFIX}/${module}/${relatedId}`;
    }
  }

  return `${DASHBOARD_PREFIX}/${module}`;
};