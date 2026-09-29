import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Target, TrendingUp, TrendingDown } from 'lucide-react';

export const OperationsSalesPerformance = () => {
  const { currencySymbol } = useCurrency();
  const { data: salesData, isLoading: salesLoading } = useGetSalesAnalyticsQuery('last_7_days');
  const { data: purchaseData, isLoading: purchaseLoading } = usePurchaseAnalyticsQuery('last_7_days');

  const isLoading = salesLoading || purchaseLoading;

  if (isLoading) {
    return (
      <Card className='rounded-2xl'>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-4'>
          <Skeleton className='h-20 w-full rounded-xl' />
          <div className='space-y-2'>
            <Skeleton className='h-3 w-full' />
            <Skeleton className='h-3 w-3/4' />
          </div>
        </CardContent>
      </Card>
    );
  }

  const sales = salesData?.data ?? salesData;
  const purchases = purchaseData?.data ?? purchaseData;

  const totalSales = sales?.total_revenue ?? 0;
  const totalPurchases = purchases?.total_expenses ?? 0;
  const netIncome = totalSales - totalPurchases;
  const profitMargin = totalSales > 0 ? (netIncome / totalSales) * 100 : 0;
  const isProfitable = netIncome >= 0;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;

  return (
    <Card className='rounded-2xl'>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Target className='h-4 w-4 text-primary' />
          Sales Performance
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className={`rounded-xl p-4 ${isProfitable ? 'bg-emerald-500/5' : 'bg-red-500/5'}`}>
          <div className='flex items-center justify-between'>
            <div>
              <p className='text-xs text-muted-foreground'>Net Income (7 days)</p>
              <p className={`text-2xl font-bold tracking-tight ${isProfitable ? 'text-emerald-500' : 'text-red-500'}`}>
                {formatAmount(netIncome)}
              </p>
            </div>
            <div className={`flex h-12 w-12 items-center justify-center rounded-2xl ${isProfitable ? 'bg-emerald-500/10' : 'bg-red-500/10'}`}>
              {isProfitable ? (
                <TrendingUp className='h-6 w-6 text-emerald-500' />
              ) : (
                <TrendingDown className='h-6 w-6 text-red-500' />
              )}
            </div>
          </div>
        </div>

        <div className='space-y-3'>
          <div>
            <div className='flex items-center justify-between text-xs mb-1.5'>
              <span className='text-muted-foreground'>Revenue</span>
              <span className='font-medium'>{formatAmount(totalSales)}</span>
            </div>
            <div className='h-2 w-full rounded-full bg-muted overflow-hidden'>
              <div
                className='h-full rounded-full bg-emerald-500 transition-all duration-500'
                style={{ width: `${totalSales > 0 ? 100 : 0}%` }}
              />
            </div>
          </div>

          <div>
            <div className='flex items-center justify-between text-xs mb-1.5'>
              <span className='text-muted-foreground'>Expenses</span>
              <span className='font-medium'>{formatAmount(totalPurchases)}</span>
            </div>
            <div className='h-2 w-full rounded-full bg-muted overflow-hidden'>
              <div
                className='h-full rounded-full bg-amber-500 transition-all duration-500'
                style={{ width: `${totalSales > 0 ? Math.min((totalPurchases / totalSales) * 100, 100) : 0}%` }}
              />
            </div>
          </div>
        </div>

        <div className='flex items-center justify-between rounded-xl bg-muted/50 px-3 py-2'>
          <span className='text-xs text-muted-foreground'>Profit Margin</span>
          <span className={`text-sm font-bold ${isProfitable ? 'text-emerald-500' : 'text-red-500'}`}>
            {profitMargin.toFixed(1)}%
          </span>
        </div>
      </CardContent>
    </Card>
  );
};
