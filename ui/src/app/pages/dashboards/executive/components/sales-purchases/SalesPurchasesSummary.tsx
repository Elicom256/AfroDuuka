import { useState } from 'react';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { ChartNoAxesColumnIncreasing } from 'lucide-react';

type TrendPoint = { date: string; amount: number; count: number };
type DashboardPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'this_month' | 'last_month';

const periods: { label: string; value: DashboardPeriod }[] = [
  { label: 'Today', value: 'today' },
  { label: 'Last 7 days', value: 'last_7_days' },
  { label: 'Last 30 days', value: 'last_30_days' },
  { label: 'This month', value: 'this_month' },
  { label: 'Last month', value: 'last_month' },
];

export const SalesPurchasesSummary = () => {
  const { currency, currencySymbol } = useCurrency();
  const [period, setPeriod] = useState<DashboardPeriod>('last_7_days');
  const { data: salesData, isLoading: salesLoading } = useGetSalesAnalyticsQuery(period);
  const { data: purchaseData, isLoading: purchaseLoading } = usePurchaseAnalyticsQuery(period);

  const isLoading = salesLoading || purchaseLoading;
  const sales = salesData?.data;
  const purchases = purchaseData?.data;
  const salesTrend: TrendPoint[] = sales?.sales_trend ?? [];
  const maxSales = Math.max(...salesTrend.map((point) => Number(point.amount) || 0), 0);

  const formatAmount = (amount: number) => `${currencySymbol ?? currency ?? ''} ${Math.round(amount).toLocaleString()}`;

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-40' />
        </CardHeader>
        <CardContent className='space-y-4'>
          <Skeleton className='h-16 w-full' />
          <Skeleton className='h-32 w-full' />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className='overflow-hidden'>
      <CardHeader className='flex flex-row flex-wrap items-center justify-between gap-2 pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <ChartNoAxesColumnIncreasing className='h-4 w-4 text-primary' />
          Sales overview
        </CardTitle>
        <Select value={period} onValueChange={(value) => setPeriod(value as DashboardPeriod)}>
          <SelectTrigger size='sm' className='w-36' aria-label='Sales date range'>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {periods.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </CardHeader>
      <CardContent>
        <div className='mb-4 grid grid-cols-3 divide-x divide-border border-y border-border'>
          <div className='min-w-0 py-3 pr-2 sm:pr-4'>
            <p className='text-[11px] text-muted-foreground'>Sales</p>
            <p className='mt-1 truncate text-sm font-semibold sm:text-base'>
              {formatAmount(Number(sales?.total_sales ?? 0))}
            </p>
          </div>
          <div className='min-w-0 px-2 py-3 sm:px-4'>
            <p className='text-[11px] text-muted-foreground'>Purchases</p>
            <p className='mt-1 truncate text-sm font-semibold sm:text-base'>
              {formatAmount(Number(purchases?.total_purchases ?? 0))}
            </p>
          </div>
          <div className='min-w-0 py-3 pl-2 sm:pl-4'>
            <p className='text-[11px] text-muted-foreground'>Transactions</p>
            <p className='mt-1 truncate text-sm font-semibold sm:text-base'>
              {Number(sales?.total_transactions ?? 0).toLocaleString()}
            </p>
          </div>
        </div>

        <div className='flex min-h-32 items-stretch gap-2 border-b border-border px-1 sm:gap-3'>
          {salesTrend.length > 0 ? (
            salesTrend.map((point) => {
              const amount = Number(point.amount) || 0;
              const height = maxSales > 0 ? Math.max((amount / maxSales) * 100, 3) : 0;

              return (
                <div key={point.date} className='group flex min-w-0 flex-1 flex-col'>
                  <div className='flex h-24 items-end'>
                    <div
                      title={`${point.date}: ${formatAmount(amount)}`}
                      className='w-full bg-primary/25 transition-colors group-hover:bg-primary/50'
                      style={{ height: `${height}%` }}
                    />
                  </div>
                  <span className='py-2 text-center text-[9px] text-muted-foreground sm:text-[10px]'>{point.date}</span>
                </div>
              );
            })
          ) : (
            <p className='flex min-h-32 w-full items-center justify-center text-xs text-muted-foreground'>
              No sales data for this period.
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  );
};
