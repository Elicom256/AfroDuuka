import { useNavigate } from 'react-router-dom';
import { Bell } from 'lucide-react';
import { toast } from 'sonner';
import { OperationsPageShell, SectionCard } from './components/Operations-page-shell';
import { NotificationItem } from '../../executive/components/notifications/NotificationItem';
import {
  useDeleteNotificationMutation,
  useGetNotificationsQuery,
  useGetUnreadCountQuery,
  useMarkAsReadMutation,
} from '@/app/store/features/branch/notifications/notificationsQuery';

export const OperationsNotificationsPage = () => {
  const navigate = useNavigate();
  const { data, isLoading } = useGetNotificationsQuery(undefined, { pollingInterval: 5000 });
  const { data: unreadData } = useGetUnreadCountQuery(undefined, { pollingInterval: 5000 });
  const [markAsRead] = useMarkAsReadMutation();
  const [deleteNotification] = useDeleteNotificationMutation();

  const notifications = data?.notifications || [];
  const unreadCount = unreadData?.unread_count ?? data?.meta?.unread ?? 0;

  const handleMarkAsRead = async (id: any) => {
    try {
      await markAsRead(id).unwrap();
    } catch {
      toast.error('Failed to mark notification as read');
    }
  };

  const handleDelete = async (id: any) => {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    try {
      await deleteNotification(id).unwrap();
      toast.success('Notification deleted');
    } catch {
      toast.error('Failed to delete notification');
    }
  };

  const handleOpen = (notification: any) => {
    navigate(`/dashboard/notifications/${notification.id}`);
  };

  return (
    <div className='space-y-6'>
      <OperationsPageShell title='Notifications' description='Receive branch updates and alerts from the system.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Unread notifications' value={unreadCount} icon={<Bell className='h-5 w-5' />} />
          <SectionCard title='Total alerts' value={notifications.length} icon={<Bell className='h-5 w-5' />} />
          <SectionCard
            title='Latest update'
            value={notifications[0]?.title ?? 'No updates'}
            icon={<Bell className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {isLoading ? (
            <p className='text-sm text-muted-foreground'>Loading notifications...</p>
          ) : notifications.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No branch notifications available yet.</p>
          ) : (
            notifications
              .slice(0, 6)
              .map((notification: any) => (
                <NotificationItem
                  key={notification.id}
                  notification={notification}
                  onMarkAsRead={handleMarkAsRead}
                  onDelete={handleDelete}
                  onOpen={handleOpen}
                />
              ))
          )}
        </div>
      </OperationsPageShell>
    </div>
  );
};
