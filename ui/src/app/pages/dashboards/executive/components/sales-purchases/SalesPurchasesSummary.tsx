import { useState } from 'react';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { ChartNoAxesColumnIncreasing } from 'lucide-react';
import { QueryErrorState } from '@/app/components/QueryErrorState';

type TrendPoint = { date: string; amount: number; count: number };
type DashboardPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'this_month' | 'last_month';

const periods: { label: string; value: DashboardPeriod }[] = [
  { label: 'Today', value: 'today' },
  { label: 'Last 7 days', value: 'last_7_days' },
  { label: 'Last 30 days', value: 'last_30_days' },
  { label: 'This month', value: 'this_month' },
  { label: 'Last month', value: 'last_month' },
];

interface SalesPurchasesSummaryProps {
  period?: DashboardPeriod;
  onPeriodChange?: (period: DashboardPeriod) => void;
}

export const SalesPurchasesSummary = ({
  period: controlledPeriod,
  onPeriodChange,
}: SalesPurchasesSummaryProps = {}) => {
  const { currency, currencySymbol } = useCurrency();
  const [internalPeriod, setInternalPeriod] = useState<DashboardPeriod>('last_7_days');
  const period = controlledPeriod ?? internalPeriod;
  const setPeriod = onPeriodChange ?? setInternalPeriod;
  const {
    data: salesData,
    isLoading: salesLoading,
    isFetching: salesFetching,
    isError: salesError,
    refetch: refetchSales,
  } = useGetSalesAnalyticsQuery(period);
  const {
    data: purchaseData,
    isLoading: purchaseLoading,
    isFetching: purchaseFetching,
    isError: purchaseError,
    refetch: refetchPurchases,
  } = usePurchaseAnalyticsQuery(period);

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

  if ((salesError && !salesData) || (purchaseError && !purchaseData)) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <CardTitle className='text-sm'>Sales overview</CardTitle>
        </CardHeader>
        <CardContent className='space-y-3'>
          {salesError && !salesData && (
            <QueryErrorState
              title='Unable to load sales summary'
              description='Sales figures could not be retrieved.'
              onRetry={refetchSales}
              retrying={salesFetching}
            />
          )}
          {purchaseError && !purchaseData && (
            <QueryErrorState
              title='Unable to load purchase summary'
              description='Purchase figures could not be retrieved.'
              onRetry={refetchPurchases}
              retrying={purchaseFetching}
            />
          )}
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
        {salesError && (
          <QueryErrorState
            title='Sales figures may be out of date'
            description='The last loaded sales figures are shown.'
            onRetry={refetchSales}
            retrying={salesFetching}
          />
        )}
        {purchaseError && (
          <QueryErrorState
            title='Purchase figures may be out of date'
            description='The last loaded purchase figures are shown.'
            onRetry={refetchPurchases}
            retrying={purchaseFetching}
          />
        )}
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
