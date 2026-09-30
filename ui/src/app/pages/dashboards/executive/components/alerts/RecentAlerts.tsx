import { useGetNotificationsQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { Skeleton } from '@/components/ui/skeleton';
import { Bell } from 'lucide-react';

type DashboardNotification = {
  id: number | string;
  title?: string | null;
  message?: string | null;
  body?: string | null;
};

export const RecentAlerts = () => {
  const { data: notificationsData, isLoading: notifLoading } = useGetNotificationsQuery({ is_read: false });
  const isLoading = notifLoading;

  const notifications = Array.isArray(notificationsData?.data)
    ? notificationsData.data
    : Array.isArray(notificationsData)
      ? notificationsData
      : [];
  if (isLoading) {
    return (
      <section className='border-t border-border/70 pt-4'>
        <div className='mb-3 flex items-center gap-2 text-sm font-medium'>
          <Bell className='h-4 w-4 text-muted-foreground' />
          Recent notifications
        </div>
        <div className='grid gap-3 sm:grid-cols-2 lg:grid-cols-3'>
          <Skeleton className='h-10 w-full' />
          <Skeleton className='h-10 w-full' />
          <Skeleton className='h-10 w-full' />
        </div>
      </section>
    );
  }

  const alerts: Array<{ id: number | string; title: string; description: string }> = notifications
    .slice(0, 5)
    .map((notification: DashboardNotification) => ({
    id: notification.id,
    title: notification.title ?? notification.message ?? 'Notification',
    description: notification.body ?? '',
    }));

  return (
    <section className='border-t border-border/70 pt-4'>
      <div className='mb-3 flex items-center gap-2 text-sm font-medium'>
        <Bell className='h-4 w-4 text-muted-foreground' />
        Recent notifications
      </div>
      {alerts.length === 0 ? (
        <p className='text-xs text-muted-foreground'>No unread notifications.</p>
      ) : (
        <div className='grid gap-x-5 gap-y-3 sm:grid-cols-2 lg:grid-cols-3'>
          {alerts.slice(0, 3).map((alert) => (
            <div key={alert.id} className='min-w-0 border-l-2 border-primary/40 pl-3'>
              <p className='truncate text-xs font-medium'>{alert.title}</p>
              {alert.description && <p className='mt-1 truncate text-xs text-muted-foreground'>{alert.description}</p>}
            </div>
          ))}
          </div>
      )}
    </section>
  );
};
