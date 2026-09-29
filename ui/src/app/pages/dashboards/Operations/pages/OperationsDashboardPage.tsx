import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { OperationsOverviewCards } from '@/app/pages/dashboards/Operations/components/overview/OperationsOverviewCards';
import { OperationsStockAlerts } from '@/app/pages/dashboards/Operations/components/stock-alerts/OperationsStockAlerts';
import { OperationsRecentActivity } from '@/app/pages/dashboards/Operations/components/recent-activity/OperationsRecentActivity';
import { OperationsQuickActions } from '@/app/pages/dashboards/Operations/components/quick-actions/OperationsQuickActions';
import { OperationsTopProducts } from '@/app/pages/dashboards/Operations/components/top-products/OperationsTopProducts';
import { OperationsSalesPerformance } from '@/app/pages/dashboards/Operations/components/sales-performance/OperationsSalesPerformance';
import { LayoutDashboard, CalendarDays, Sparkles } from 'lucide-react';

export const OperationsDashboardPage = () => {
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

  const getGreeting = () => {
    const hour = now.getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  };

  return (
    <div className='space-y-6'>
      <div className='relative overflow-hidden rounded-2xl bg-linear-to-br from-primary/10 via-primary/5 to-accent/10 p-6 border border-primary/10'>
        <div className='absolute top-0 right-0 w-64 h-64 bg-primary/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl' />
        <div className='absolute bottom-0 left-0 w-48 h-48 bg-accent/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl' />
        <div className='relative flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <div className='flex items-center gap-2 text-sm font-medium text-muted-foreground'>
              <LayoutDashboard className='h-4 w-4' />
              Branch Dashboard
              <Sparkles className='h-3.5 w-3.5 text-yellow-500' />
            </div>
            <h1 className='text-2xl font-bold tracking-tight mt-1'>
              {getGreeting()}, {username} 👋
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

      <OperationsOverviewCards />

      <div className='grid gap-4 lg:grid-cols-3'>
        <OperationsQuickActions />
        <OperationsTopProducts />
        <OperationsSalesPerformance />
      </div>

      <div className='grid gap-4 lg:grid-cols-2'>
        <OperationsStockAlerts />
        <OperationsRecentActivity />
      </div>
    </div>
  );
};
