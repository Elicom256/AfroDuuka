import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { CheckCheck, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

import {
  useClearAllMutation,
  useDeleteNotificationMutation,
  useGetNotificationsQuery,
  useGetUnreadCountQuery,
  useMarkAllAsReadMutation,
  useMarkAsReadMutation,
} from '@/app/store/features/branch/notifications/notificationsQuery';
import { NotificationItem } from '../components/notifications/NotificationItem';
import { StatsCard } from '../components/notifications/StatsCard';
import { notificationRouteForType, notificationTypeLabel } from '../components/notifications/notificationUtils';

const FILTER_OPTIONS = [
  { type: '', label: 'All' },
  { type: 'low_stock', label: 'Stock' },
  { type: 'new_sale', label: 'Sales' },
  { type: 'new_purchase', label: 'Purchases' },
  { type: 'payment_received', label: 'Payments' },
  { type: 'overdue_payment', label: 'Overdue' },
  { type: 'attendance', label: 'Attendance' },
];

export const AdminNotificationsPage = () => {
  const [typeFilter, setTypeFilter] = useState('');
  const navigate = useNavigate();

  const { data, isLoading, isError } = useGetNotificationsQuery(typeFilter ? { type: typeFilter } : undefined, {
    pollingInterval: 5000,
  });
  const { data: unreadData } = useGetUnreadCountQuery(undefined, { pollingInterval: 5000 });
  const [markAsRead] = useMarkAsReadMutation();
  const [markAllAsRead] = useMarkAllAsReadMutation();
  const [deleteNotification] = useDeleteNotificationMutation();
  const [clearAll] = useClearAllMutation();

  const notifications = data?.notifications || [];
  const unreadByType = unreadData?.unread_by_type ?? data?.meta?.unread_by_type ?? {};
  const unreadCount = unreadData?.unread_count ?? data?.meta?.unread ?? 0;

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

  const handleClearAll = async () => {
    if (!confirm('Delete ALL notifications? This cannot be undone.')) return;
    try {
      const res = await clearAll().unwrap();
      toast.success(res?.message ?? 'All notifications cleared');
    } catch (err) {
      toast.error('Failed to clear notifications');
    }
  };

  const handleOpen = (notification: any) => {
    const route = notificationRouteForType(notification.type, 'admin');
    if (route) {
      navigate(route);
      if (!notification.is_read) {
        markAsRead(notification.id);
      }
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

        <div className='flex gap-2'>
          <Button variant='outline' onClick={handleMarkAllAsRead} disabled={unreadCount === 0}>
            <CheckCheck className='mr-2 h-4 w-4' />
            Mark all as read
          </Button>
          <Button variant='destructive' onClick={handleClearAll} disabled={notifications.length === 0}>
            <Trash2 className='mr-2 h-4 w-4' />
            Clear All
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <StatsCard notifications={notifications} unreadCount={unreadCount} unreadByType={unreadByType} />

      {/* Notifications List */}
      <Card>
        <CardHeader>
          <div className='flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
            <div>
              <CardTitle>Recent Notifications</CardTitle>
              <CardDescription>Latest updates from your business system</CardDescription>
            </div>
            {/* Filter chips */}
            <div className='flex flex-wrap gap-2'>
              {FILTER_OPTIONS.map((opt) => {
                const isActive = typeFilter === opt.type;
                const count = opt.type ? (unreadByType[opt.type] ?? 0) : unreadCount;
                return (
                  <button
                    key={opt.type}
                    type='button'
                    onClick={() => setTypeFilter(opt.type)}
                    className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition ${
                      isActive ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background hover:bg-muted/60'
                    }`}
                  >
                    {opt.label}
                    {count > 0 && (
                      <span
                        className={`inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] ${
                          isActive ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-destructive text-destructive-foreground'
                        }`}
                      >
                        {count}
                      </span>
                    )}
                  </button>
                );
              })}
            </div>
          </div>
        </CardHeader>

        <CardContent className='space-y-4'>
          {isLoading ? (
            [...Array(5)].map((_, i) => <Skeleton key={i} className='h-24 w-full' />)
          ) : isError ? (
            <p className='text-center py-12 text-red-500'>Failed to load notifications</p>
          ) : notifications.length === 0 ? (
            <div className='text-center py-16 text-muted-foreground'>
              {typeFilter ? `No ${notificationTypeLabel(typeFilter).toLowerCase()} notifications yet.` : "No notifications yet. You're all caught up!"}
            </div>
          ) : (
            notifications.map((notification: any) => (
              <NotificationItem
                key={notification.id}
                notification={notification}
                onMarkAsRead={handleMarkAsRead}
                onDelete={handleDelete}
                onOpen={handleOpen}
              />
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
};