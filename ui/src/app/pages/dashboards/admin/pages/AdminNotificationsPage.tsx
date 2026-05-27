import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { CheckCheck, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

import {
  useDeleteNotificationMutation,
  useGetNotificationsQuery,
  useMarkAllAsReadMutation,
  useMarkAsReadMutation,
} from '@/app/store/features/branch/notifications/notificationsQuery';
import { NotificationItem } from '../components/notifications/NotificationItem';
import { StatsCard } from '../components/notifications/StatsCard';

export const AdminNotificationsPage = () => {
  const { data, isLoading, isError } = useGetNotificationsQuery(undefined, { pollingInterval: 3000 });
  const [markAsRead] = useMarkAsReadMutation();
  const [markAllAsRead] = useMarkAllAsReadMutation();
  const [deleteNotification] = useDeleteNotificationMutation();

  const notifications = data?.notifications || [];
  const unreadCount = notifications.filter((n: any) => !n.is_read).length;
  // console.log('notifications==>', data);
  const handleMarkAsRead = async (id: any) => {
    try {
      await markAsRead(id).unwrap();
    } catch (err) {
      toast.error('Failed to mark notification as read');
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      const res = await markAllAsRead().unwrap();
      console.log('read=>', res);
      if (res) {
        toast.success(res.message ?? 'All marked as read');
      }
    } catch (err) {
      toast.error('Failed to mark all as read');
    }
  };

  const handleDelete = async (id: any) => {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    try {
      await deleteNotification(id).unwrap();
      toast.success('Notification deleted');
    } catch (err) {
      toast.error('Failed to delete notification');
    }
  };

  return (
    <div className='space-y-6 p-6'>
      {/* Header */}
      <div className='flex flex-col gap-4 md:flex-row md:items-center md:justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight'>Notifications</h1>
          <p className='text-muted-foreground'>Stay updated with your business activities</p>
        </div>

        {notifications?.length > 0 && (
          <div className='flex gap-2'>
            <Button variant='outline' onClick={handleMarkAllAsRead} disabled={unreadCount === 0}>
              <CheckCheck className='mr-2 h-4 w-4' />
              Mark all as read
            </Button>
            <Button variant='destructive'>
              <Trash2 className='mr-2 h-4 w-4' />
              Clear All
            </Button>
          </div>
        )}
      </div>

      {/* Stats Cards */}
      <StatsCard notifications={notifications} unreadCount={unreadCount} />
      {/* <div className='grid gap-4 md:grid-cols-4 xl:grid-cols-4'></div> */}

      {/* Notifications List */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Notifications</CardTitle>
          <CardDescription>Latest updates from your business system</CardDescription>
        </CardHeader>

        <CardContent className='space-y-4'>
          {isLoading ? (
            [...Array(5)].map((_, i) => <Skeleton key={i} className='h-24 w-full' />)
          ) : isError ? (
            <p className='text-center py-12 text-red-500'>Failed to load notifications</p>
          ) : notifications.length === 0 ? (
            <div className='text-center py-16 text-muted-foreground'>No notifications yet. You're all caught up!</div>
          ) : (
            notifications.map((notification: any) => (
              <NotificationItem
                key={notification.id}
                notification={notification}
                onMarkAsRead={handleMarkAsRead}
                onDelete={handleDelete}
              />
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
};
