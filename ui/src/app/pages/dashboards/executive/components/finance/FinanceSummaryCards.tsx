import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useCurrency } from '@/app/hooks/useCurrency';
import { TrendingUp, TrendingDown, DollarSign, Wallet, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils'; // assuming you have this from shadcn

type FinanceSummaryCardsProps = {
  gross_revenue?: number;
  total_refunds?: number;
  total_revenue: number;
  total_expenses: number;
  net_profit: number;
  cash_balance: number;

  /**
   * Adjustments recorded before a direction became required. They are excluded from
   * cash_balance, because nothing says which way the money moved, so the figure is only
   * as complete as this count allows.
   */
  unsigned_adjustments?: number;
};

export const FinanceSummaryCards = ({
  gross_revenue,
  total_refunds,
  total_revenue,
  total_expenses,
  net_profit,
  cash_balance,
  unsigned_adjustments: unsignedAdjustments = 0,
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
      {unsignedAdjustments > 0 && (
        <Alert className='col-span-full border-amber-200 bg-amber-50/50 dark:border-amber-900 dark:bg-amber-950/50'>
          <AlertTriangle className='text-amber-600 dark:text-amber-400' />
          <AlertTitle className='text-amber-800 dark:text-amber-300'>
            Cash balance may be incomplete
          </AlertTitle>
          <AlertDescription className='text-amber-700 dark:text-amber-400'>
            {unsignedAdjustments === 1
              ? '1 manual adjustment has no recorded direction'
              : `${unsignedAdjustments} manual adjustments have no recorded direction`}
            , so {unsignedAdjustments === 1 ? 'it is' : 'they are'} excluded from the cash balance below. Mark{' '}
            {unsignedAdjustments === 1 ? 'it' : 'each one'} as money in or money out from the transactions table
            below to include {unsignedAdjustments === 1 ? 'it' : 'them'} in the figure.
          </AlertDescription>
        </Alert>
      )}

      {/* Revenue */}
      <Card className="group border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/50 transition-all hover:shadow-md hover:-translate-y-0.5">
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
            Net Revenue
          </CardTitle>
          <div className="rounded-full bg-emerald-100 p-1.5 dark:bg-emerald-900">
            <TrendingUp className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-semibold tracking-tight text-emerald-700 dark:text-emerald-300">
            {currency} {formatCurrency(total_revenue)}
          </p>
          {gross_revenue !== undefined && total_refunds !== undefined && (
            <div className="mt-2 space-y-1">
              <p className="text-xs text-muted-foreground">
                Gross: {currency} {formatCurrency(gross_revenue)}
              </p>
              <p className="text-xs text-red-500">
                Refunds: -{currency} {formatCurrency(total_refunds)}
              </p>
            </div>
          )}
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
