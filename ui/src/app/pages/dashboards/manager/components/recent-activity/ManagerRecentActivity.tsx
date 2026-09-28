import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchasesQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useGetNotificationsQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Activity, DollarSign, ShoppingCart, Bell } from 'lucide-react';

export const ManagerRecentActivity = () => {
  const { data: salesData, isLoading: salesLoading } = useSalesQuery();
  const { data: purchasesData, isLoading: purchasesLoading } = usePurchasesQuery();
  const { data: notifData, isLoading: notifLoading } = useGetNotificationsQuery({ is_read: false });

  const isLoading = salesLoading || purchasesLoading || notifLoading;

  const sales = salesData?.sales ?? salesData?.data ?? salesData ?? [];
  const purchases = purchasesData?.purchases ?? purchasesData?.data ?? purchasesData ?? [];
  const notifications = notifData?.data ?? notifData ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
          <Skeleton className='h-4 w-5/6' />
        </CardContent>
      </Card>
    );
  }

  const activities = [
    ...sales.slice(0, 3).map((s: any) => ({
      id: `sale-${s.id}`,
      type: 'sale' as const,
      title: `Sale #${s.id}`,
      description: `Customer: ${s.customer?.name ?? 'Walk-in'}`,
      amount: s.total_amount,
      time: s.created_at,
    })),
    ...purchases.slice(0, 3).map((p: any) => ({
      id: `purchase-${p.id}`,
      type: 'purchase' as const,
      title: `Purchase #${p.id}`,
      description: `Supplier: ${p.supplier?.company_name ?? 'Unknown'}`,
      amount: p.total_amount,
      time: p.created_at,
    })),
    ...notifications.slice(0, 2).map((n: any) => ({
      id: `notif-${n.id}`,
      type: 'notification' as const,
      title: n.title ?? 'Notification',
      description: n.body ?? n.message ?? '',
      amount: null,
      time: n.created_at,
    })),
  ]
    .filter((a) => a.time)
    .sort((a, b) => new Date(b.time).getTime() - new Date(a.time).getTime())
    .slice(0, 8);

  const getIcon = (type: string) => {
    switch (type) {
      case 'sale': return DollarSign;
      case 'purchase': return ShoppingCart;
      default: return Bell;
    }
  };

  const getBadgeVariant = (type: string) => {
    switch (type) {
      case 'sale': return 'default' as const;
      case 'purchase': return 'secondary' as const;
      default: return 'outline' as const;
    }
  };

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Activity className='h-4 w-4 text-blue-500' />
          Recent Activity
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-1'>
        {activities.length === 0 && (
          <p className='text-xs text-muted-foreground'>No recent activity.</p>
        )}
        {activities.map((activity) => {
          const Icon = getIcon(activity.type);
          return (
            <div
              key={activity.id}
              className='flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-muted/50'
            >
              <div className='flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted'>
                <Icon className='h-3.5 w-3.5 text-muted-foreground' />
              </div>
              <div className='min-w-0 flex-1'>
                <div className='flex items-center gap-2'>
                  <p className='truncate text-sm font-medium'>{activity.title}</p>
                  <Badge variant={getBadgeVariant(activity.type)} className='shrink-0 text-[10px]'>
                    {activity.type}
                  </Badge>
                </div>
                <p className='truncate text-xs text-muted-foreground'>{activity.description}</p>
              </div>
              {activity.amount != null && (
                <span className='shrink-0 text-xs font-medium'>
                  {activity.type === 'sale' ? '+' : '-'}
                  {Math.round(Number(activity.amount)).toLocaleString()}
                </span>
              )}
            </div>
          );
        })}
      </CardContent>
    </Card>
  );
};
