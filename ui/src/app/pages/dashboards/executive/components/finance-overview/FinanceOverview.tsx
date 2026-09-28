import { useGetFinanceDashboardQuery } from '@/app/store/features/finance/financeQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Wallet, TrendingUp, TrendingDown, PiggyBank } from 'lucide-react';

export const FinanceOverview = () => {
  const { currencySymbol } = useCurrency();
  const { data, isLoading } = useGetFinanceDashboardQuery({});

  const dashboard = data?.data ?? data;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;

  if (isLoading) {
    return (
      <Card>
        <CardHeader className="pb-3">
          <Skeleton className="h-5 w-32" />
        </CardHeader>
        <CardContent className="space-y-3">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />
        </CardContent>
      </Card>
    );
  }

  const revenue = dashboard?.total_revenue ?? dashboard?.revenue ?? 0;
  const expenses = dashboard?.total_expenses ?? dashboard?.expenses ?? 0;
  const netCashFlow = dashboard?.net_cash_flow ?? (revenue - expenses);
  const cashInHand = dashboard?.cash_in_hand ?? dashboard?.balance ?? 0;

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="flex items-center gap-2 text-sm">
          <Wallet className="h-4 w-4 text-indigo-500" />
          Finance Overview
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-1">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <TrendingUp className="h-3 w-3 text-emerald-500" />
              Revenue
            </div>
            <p className="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
              {formatAmount(revenue)}
            </p>
          </div>
          <div className="space-y-1">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <TrendingDown className="h-3 w-3 text-red-500" />
              Expenses
            </div>
            <p className="text-lg font-semibold text-red-600 dark:text-red-400">
              {formatAmount(expenses)}
            </p>
          </div>
        </div>
        <div className="mt-3 space-y-2 border-t pt-3">
          <div className="flex items-center justify-between text-xs">
            <span className="flex items-center gap-1.5 text-muted-foreground">
              <PiggyBank className="h-3 w-3" />
              Net Cash Flow
            </span>
            <span className={`font-medium ${netCashFlow >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
              {formatAmount(netCashFlow)}
            </span>
          </div>
          <div className="flex items-center justify-between text-xs">
            <span className="text-muted-foreground">Cash in Hand</span>
            <span className="font-medium">{formatAmount(cashInHand)}</span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};
