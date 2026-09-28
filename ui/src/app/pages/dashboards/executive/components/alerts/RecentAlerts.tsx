import { useGetNotificationsQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { useProductRestockingQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Bell, AlertTriangle, Package } from 'lucide-react';

export const RecentAlerts = () => {
  const { data: notificationsData, isLoading: notifLoading } = useGetNotificationsQuery({ is_read: false });
  const { data: restockingData, isLoading: restockLoading } = useProductRestockingQuery();

  const isLoading = notifLoading || restockLoading;

  const notifications = Array.isArray(notificationsData?.data)
    ? notificationsData.data
    : Array.isArray(notificationsData)
      ? notificationsData
      : [];
  const atRisk = restockingData?.data?.predictions?.filter((p: any) => p.is_at_risk) ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className="pb-3">
          <Skeleton className="h-5 w-32" />
        </CardHeader>
        <CardContent className="space-y-3">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />
        </CardContent>
      </Card>
    );
  }

  const alerts = [
    ...notifications.slice(0, 3).map((n: any) => ({
      id: `notif-${n.id}`,
      type: 'notification' as const,
      title: n.title ?? n.message ?? 'Notification',
      description: n.body ?? '',
      time: n.created_at,
    })),
    ...atRisk.slice(0, 3).map((p: any) => ({
      id: `restock-${p.id}`,
      type: 'restock' as const,
      title: p.name,
      description: `Stock: ${p.quantity} · ~${p.days_until_out ?? 'N/A'} days left`,
      time: null,
    })),
  ];

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="flex items-center gap-2 text-sm">
          <Bell className="h-4 w-4 text-blue-500" />
          Recent Alerts
          {alerts.length > 0 && (
            <Badge variant="secondary" className="ml-auto text-xs">{alerts.length}</Badge>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {alerts.length === 0 && (
          <p className="text-xs text-muted-foreground">No active alerts. Everything looks good.</p>
        )}
        {alerts.slice(0, 5).map((alert) => (
          <div
            key={alert.id}
            className="flex items-start gap-2.5 rounded-lg border border-border/50 px-3 py-2"
          >
            {alert.type === 'notification' ? (
              <Bell className="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-500" />
            ) : (
              <AlertTriangle className="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
            )}
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium">{alert.title}</p>
              {alert.description && (
                <p className="truncate text-xs text-muted-foreground">{alert.description}</p>
              )}
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
};
