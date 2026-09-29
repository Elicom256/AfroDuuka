export const NOTIFICATION_TYPE_LABELS: Record<string, string> = {
  low_stock: 'Stock Alert',
  new_sale: 'Sale',
  new_purchase: 'Purchase',
  payment_received: 'Payment Received',
  overdue_payment: 'Overdue Payment',
  attendance: 'Attendance',
};

export const notificationTypeLabel = (type: string): string => NOTIFICATION_TYPE_LABELS[type] ?? type.replace('_', ' ');

export const notificationRouteForType = (type: string, scope: 'executive' | 'branch_manager' | 'operations' | 'staff'): string | null => {
  const module: Record<string, string> = {
    low_stock: 'products',
    new_sale: 'sales',
    new_purchase: 'purchases',
    payment_received: 'sales',
    overdue_payment: 'customers',
    attendance: 'attendance',
  };
  const target = module[type];
  if (!target) return null;
  return `/${scope}/${target}`;
};