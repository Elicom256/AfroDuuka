import { AiChat } from '@/components/ai/AiChat';
import { useState } from 'react';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { StockHealth } from '@/app/pages/dashboards/executive/components/stock-health/StockHealth';
import { SalesPurchasesSummary } from '@/app/pages/dashboards/executive/components/sales-purchases/SalesPurchasesSummary';
import { SalesStatCards } from '@/app/pages/dashboards/executive/components/sales-purchases/SalesStatCards';
import { RevenueChart } from '@/app/pages/dashboards/executive/components/overview/RevenueChart';
import { CashPosition } from '@/app/pages/dashboards/executive/components/overview/CashPosition';
import { RecentActivity } from '@/app/pages/dashboards/executive/components/overview/RecentActivity';
import { TopProducts } from '@/app/pages/dashboards/executive/components/overview/TopProducts';
import { RecentSales } from '@/app/pages/dashboards/executive/components/overview/RecentSales';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { CalendarDays, MessageSquareText } from 'lucide-react';
import type { LandingPeriod } from '@/app/pages/dashboards/executive/components/landingPeriods';

export const BranchManagerDashboardPage = () => {
  const [assistantOpen, setAssistantOpen] = useState(false);
  const [period, setPeriod] = useState<LandingPeriod>('last_7_days');
  const { data: userData } = useLoggedinUserQuery();
  const username = userData?.data?.username ?? 'there';
  const branchName = userData?.data?.businessBranch?.name ?? 'your branch';

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <div className='space-y-3'>
      <div className='flex flex-col gap-2 border-b border-border/70 pb-3 sm:flex-row sm:items-center sm:justify-between'>
        <div>
          <h1 className='text-lg font-medium'>Welcome back, {username}</h1>
          <p className='mt-0.5 text-xs text-muted-foreground'>{branchName}</p>
        </div>
        <div className='flex flex-wrap items-center gap-2'>
          <div className='flex items-center gap-2 text-xs text-muted-foreground'>
            <CalendarDays className='h-4 w-4' />
            {dateStr}
          </div>
          <Sheet open={assistantOpen} onOpenChange={setAssistantOpen}>
            <SheetTrigger asChild>
              <Button variant='outline' size='sm'>
                <MessageSquareText className='mr-2 h-4 w-4' />
                Ask assistant
              </Button>
            </SheetTrigger>
            <SheetContent side='right' className='w-full sm:max-w-md'>
              <SheetHeader>
                <SheetTitle>Business assistant</SheetTitle>
              </SheetHeader>
              <div className='h-[calc(100%-4rem)] pt-4'>
                <AiChat />
              </div>
            </SheetContent>
          </Sheet>
        </div>
      </div>

      <SalesStatCards period={period} />

      <div className='grid gap-3 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]'>
        <RevenueChart period={period} />
        <CashPosition period={period} />
      </div>

      <div className='grid gap-3 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]'>
        <SalesPurchasesSummary period={period} onPeriodChange={setPeriod} />
        <StockHealth />
      </div>

      <div className='grid gap-3 lg:grid-cols-3'>
        <RecentActivity />
        <TopProducts period={period} />
        <RecentSales />
      </div>
    </div>
  );
};
