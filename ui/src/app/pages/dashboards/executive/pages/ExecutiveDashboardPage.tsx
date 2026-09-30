import { AiChat } from '@/components/ai/AiChat';
import { useState } from 'react';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { StockHealth } from '@/app/pages/dashboards/executive/components/stock-health/StockHealth';
import { SalesPurchasesSummary } from '@/app/pages/dashboards/executive/components/sales-purchases/SalesPurchasesSummary';
import { RecentAlerts } from '@/app/pages/dashboards/executive/components/alerts/RecentAlerts';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { CalendarDays, MessageSquareText } from 'lucide-react';

export const ExecutiveDashboardPage = () => {
  const [assistantOpen, setAssistantOpen] = useState(false);
  const { data: userData } = useLoggedinUserQuery();
  const username = userData?.data?.username ?? 'there';
  const businessName = userData?.data?.business?.name ?? 'your business';

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <div className='space-y-5'>
      <div className='flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <h1 className='text-2xl font-semibold tracking-tight'>Welcome back, {username}</h1>
            <p className='mt-1 text-sm text-muted-foreground'>A business overview for {businessName}.</p>
          </div>
          <div className='flex flex-wrap items-center gap-3'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
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

      <div className='grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]'>
        <SalesPurchasesSummary />
        <StockHealth />
      </div>

      <RecentAlerts />
    </div>
  );
};
