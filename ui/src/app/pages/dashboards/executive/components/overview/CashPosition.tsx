import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useCashFlowAnalyticsQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Wallet, ArrowUpRight, ArrowDownRight } from 'lucide-react';
import { landingPeriodLabel, type LandingPeriod } from '../landingPeriods';
import { QueryErrorState } from '@/app/components/QueryErrorState';

export const CashPosition = ({ period }: { period: LandingPeriod }) => {
  const { currency, currencySymbol } = useCurrency();
  const { data, isLoading, isFetching, isError, refetch } = useCashFlowAnalyticsQuery(period);

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-10 w-full' />
          <Skeleton className='h-10 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError && !data) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <CardTitle className='text-sm'>Cash position</CardTitle>
        </CardHeader>
        <CardContent>
          <QueryErrorState
            title='Unable to load cash position'
            description='Revenue and expense totals are unavailable.'
            onRetry={refetch}
            retrying={isFetching}
          />
        </CardContent>
      </Card>
    );
  }

  const analytics = data?.data;
  const revenue = Number(analytics?.total_revenue ?? 0);
  const expenses = Number(analytics?.total_expenses ?? 0);
  const net = Number(analytics?.net_cash_flow ?? 0);
  const isPositive = net >= 0;
  const symbol = currencySymbol ?? currency ?? '';

  const formatAmount = (amount: number) => `${symbol} ${Math.round(amount).toLocaleString()}`;

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Wallet className='h-4 w-4 text-primary' />
          Cash position
        </CardTitle>
        <CardDescription>{landingPeriodLabel(period)}</CardDescription>
      </CardHeader>
      <CardContent className='space-y-3'>
        {isError && (
          <QueryErrorState
            title='Cash position may be out of date'
            description='The last loaded figures are shown.'
            onRetry={refetch}
            retrying={isFetching}
          />
        )}
        <div className='rounded-xl border border-border/60 p-3'>
          <p className='text-[11px] text-muted-foreground'>Revenue</p>
          <p className='mt-0.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400'>{formatAmount(revenue)}</p>
        </div>
        <div className='rounded-xl border border-border/60 p-3'>
          <p className='text-[11px] text-muted-foreground'>Expenses</p>
          <p className='mt-0.5 text-sm font-semibold text-red-600 dark:text-red-400'>{formatAmount(expenses)}</p>
        </div>
        <div className={`rounded-xl p-3 ${isPositive ? 'bg-emerald-500/10' : 'bg-red-500/10'}`}>
          <p className='text-[11px] text-muted-foreground'>Net cash flow</p>
          <p
            className={`mt-0.5 flex items-center gap-1 text-sm font-semibold ${isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}
          >
            {isPositive ? <ArrowUpRight className='h-3.5 w-3.5' /> : <ArrowDownRight className='h-3.5 w-3.5' />}
            {formatAmount(net)}
          </p>
        </div>
      </CardContent>
    </Card>
  );
};
