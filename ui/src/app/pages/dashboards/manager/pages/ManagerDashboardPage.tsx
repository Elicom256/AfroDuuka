import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { ManagerOverviewCards } from '@/app/pages/dashboards/manager/components/overview/ManagerOverviewCards';
import { ManagerStockAlerts } from '@/app/pages/dashboards/manager/components/stock-alerts/ManagerStockAlerts';
import { ManagerRecentActivity } from '@/app/pages/dashboards/manager/components/recent-activity/ManagerRecentActivity';
import { LayoutDashboard, CalendarDays } from 'lucide-react';

export const ManagerDashboardPage = () => {
  const { flagEmoji } = useCurrency();
  const { data: userData } = useLoggedinUserQuery();

  const username = userData?.data?.username ?? 'there';
  const businessName = userData?.data?.business?.name ?? 'your business';
  const branchName = userData?.data?.business_branch?.name ?? 'your branch';

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <div className='space-y-6'>
      <div className='relative overflow-hidden rounded-2xl bg-linear-to-br from-primary/5 via-primary/10 to-primary/5 p-6 border border-primary/10'>
        <div className='absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl' />
        <div className='relative flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <div className='flex items-center gap-2 text-sm font-medium text-muted-foreground'>
              <LayoutDashboard className='h-4 w-4' />
              Branch Dashboard
            </div>
            <h1 className='text-2xl font-bold tracking-tight mt-1'>
              Welcome back, {username}
            </h1>
            <p className='text-sm text-muted-foreground mt-0.5'>
              {businessName} &middot; {branchName}
            </p>
          </div>
          <div className='flex items-center gap-2 text-sm text-muted-foreground mt-3 sm:mt-0'>
            <CalendarDays className='h-4 w-4' />
            {dateStr}
            {flagEmoji && <span className='ml-1 text-base'>{flagEmoji}</span>}
          </div>
        </div>
      </div>

      <ManagerOverviewCards />

      <div className='grid gap-4 lg:grid-cols-2'>
        <ManagerStockAlerts />
        <ManagerRecentActivity />
      </div>
    </div>
  );
};
