import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/app/hooks/useCurrency';
import { TrendingUp, TrendingDown, DollarSign, Wallet } from 'lucide-react';
import { cn } from '@/lib/utils'; // assuming you have this from shadcn

type FinanceSummaryCardsProps = {
  total_revenue: number;
  total_expenses: number;
  net_profit: number;
  cash_balance: number;
};

export const FinanceSummaryCards = ({
  total_revenue,
  total_expenses,
  net_profit,
  cash_balance,
}: FinanceSummaryCardsProps) => {
  const { currency } = useCurrency();
  const isProfitPositive = net_profit >= 0;

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(Math.abs(amount));
  };

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {/* Revenue */}
      <Card className="group border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
            Total Revenue
          </CardTitle>
          <div className="rounded-full bg-emerald-100 p-1.5 dark:bg-emerald-900">
            <TrendingUp className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-emerald-700 dark:text-emerald-300">
            {currency} {formatCurrency(total_revenue)}
          </p>
        </CardContent>
      </Card>

      {/* Expenses */}
      <Card className="group border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-red-700 dark:text-red-400">
            Total Expenses
          </CardTitle>
          <div className="rounded-full bg-red-100 p-1.5 dark:bg-red-900">
            <TrendingDown className="h-5 w-5 text-red-600 dark:text-red-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-red-700 dark:text-red-300">
            {currency} {formatCurrency(total_expenses)}
          </p>
        </CardContent>
      </Card>

      {/* Net Profit */}
      <Card
        className={cn(
          'group transition-all hover:shadow-md hover:-translate-y-0.5',
          isProfitPositive
            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/50'
            : 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50'
        )}
      >
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle
            className={cn(
              'text-sm font-medium',
              isProfitPositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'
            )}
          >
            Net Profit
          </CardTitle>
          <div
            className={cn(
              'rounded-full p-1.5',
              isProfitPositive ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-red-100 dark:bg-red-900'
            )}
          >
            <DollarSign
              className={cn(
                'h-5 w-5',
                isProfitPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'
              )}
            />
          </div>
        </CardHeader>
        <CardContent>
          <p
            className={cn(
              'text-3xl font-semibold tracking-tight',
              isProfitPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'
            )}
          >
            {currency} {formatCurrency(net_profit)}
          </p>
          {isProfitPositive ? (
            <p className="mt-1 text-xs text-emerald-600 flex items-center gap-1 dark:text-emerald-400">
              <TrendingUp className="h-3 w-3" /> Positive
            </p>
          ) : (
            <p className="mt-1 text-xs text-red-600 flex items-center gap-1 dark:text-red-400">
              <TrendingDown className="h-3 w-3" /> Loss
            </p>
          )}
        </CardContent>
      </Card>

      {/* Cash Balance */}
      <Card className="group border-purple-200 bg-purple-50/50 dark:border-purple-900 dark:bg-purple-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-purple-700 dark:text-purple-400">
            Cash Balance
          </CardTitle>
          <div className="rounded-full bg-purple-100 p-1.5 dark:bg-purple-900">
            <Wallet className="h-5 w-5 text-purple-600 dark:text-purple-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-purple-700 dark:text-purple-300">
            {currency} {formatCurrency(cash_balance)}
          </p>
        </CardContent>
      </Card>
    </div>
  );
};