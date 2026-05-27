import { Bell } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';

export const ManagerNotificationsPage = () => {
  let notifications: any = [];
  const unreadCount = notifications.filter((item: any) => !item.read).length;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Notifications' description='Receive branch updates and alerts from the system.'>
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
          {notifications.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No branch notifications available yet.</p>
          ) : (
            notifications.slice(0, 6).map((notification: any, index: number) => (
              <div key={notification.id ?? index} className='rounded-3xl border border-border/70 bg-background p-4'>
                <p className='font-semibold'>{notification.title ?? 'Notification'}</p>
                <p className='text-sm text-muted-foreground'>
                  {notification.message ?? notification.description ?? ''}
                </p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
