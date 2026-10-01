import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from '@/components/ThemeToggle';
import { UserProfile } from '../auth/UserProfile';
import { Bell, Menu } from 'lucide-react';
import { useGetUnreadCountQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { DASHBOARD_PREFIX } from '@/lib/rolePrefix';

type DashboardTopBarProps = {
  role: string;
  userData?: {
    data?: {
      name?: string;
      username?: string;
      business?: { name?: string };
    };
  } | null;
  onMenuClick: () => void;
};

export const DashboardTopBar = ({ role, userData, onMenuClick }: DashboardTopBarProps) => {
  const { data: unreadData } = useGetUnreadCountQuery(undefined, { pollingInterval: 60000 });
  const unreadCount = unreadData?.unread_count ?? 0;

  return (
    <header className='sticky top-0 z-40 flex h-16 items-center justify-between border-b border-border/70 bg-background/90 px-4 backdrop-blur-md sm:px-6'>
      <div className='flex min-w-0 items-center gap-3'>
        <Button
          type='button'
          variant='outline'
          size='icon'
          className='h-9 w-9 shrink-0 md:hidden'
          aria-label='Open navigation menu'
          onClick={onMenuClick}
        >
          <Menu className='h-4 w-4' />
        </Button>
        <img src='/afroduuka.png' alt='' className='h-9 w-9 shrink-0 object-contain' />
        <div className='min-w-0'>
          <p className='truncate text-sm font-semibold'>DuukaFlow</p>
          <p className='truncate text-xs text-muted-foreground'>{userData?.data?.business?.name ?? role}</p>
        </div>
      </div>

      <div className='ml-4 flex shrink-0 items-center gap-2 sm:gap-4'>
        <ThemeToggle compact />
        <Button
          asChild
          type='button'
          variant='ghost'
          size='icon'
          className='relative h-9 w-9'
          aria-label='Notifications'
          title='Notifications'
        >
          <Link to={`${DASHBOARD_PREFIX}/notifications`}>
            <Bell className='h-4 w-4' />
            {unreadCount > 0 && (
              <span className='absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-destructive-foreground'>
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </Link>
        </Button>
        {userData && <UserProfile data={userData} compact />}
      </div>
    </header>
  );
};
