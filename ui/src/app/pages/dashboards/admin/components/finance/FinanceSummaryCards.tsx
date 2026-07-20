import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/app/hooks/useCurrency';
import { TrendingUp, TrendingDown, DollarSign, Wallet } from 'lucide-react';

type FinanceSummaryCardsProps = {
  total_revenue: number;
  total_expenses: number;
  net_profit: number;
  cash_balance: number;
};

export const FinanceSummaryCards = ({ total_revenue, total_expenses, net_profit, cash_balance }: FinanceSummaryCardsProps) => {
  const { currency } = useCurrency();
  const isProfitPositive = net_profit >= 0;

  return (
    <div className='grid gap-4 md:grid-cols-2 lg:grid-cols-4'>
      <Card className='border-emerald-200 bg-emerald-50/40'>
        <CardHeader className='flex flex-row items-center justify-between pb-2'>
          <CardTitle className='text-sm font-medium text-emerald-700'>Total Revenue</CardTitle>
          <TrendingUp className='h-5 w-5 text-emerald-600' />
        </CardHeader>
        <CardContent>
          <p className='text-2xl font-bold text-emerald-700'>
            {currency} {total_revenue.toLocaleString()}
          </p>
        </CardContent>
      </Card>

      <Card className='border-red-200 bg-red-50/40'>
        <CardHeader className='flex flex-row items-center justify-between pb-2'>
          <CardTitle className='text-sm font-medium text-red-700'>Total Expenses</CardTitle>
          <TrendingDown className='h-5 w-5 text-red-600' />
        </CardHeader>
        <CardContent>
          <p className='text-2xl font-bold text-red-700'>
            {currency} {total_expenses.toLocaleString()}
          </p>
        </CardContent>
      </Card>

      <Card className={net_profit >= 0 ? 'border-emerald-200 bg-emerald-50/40' : 'border-red-200 bg-red-50/40'}>
        <CardHeader className='flex flex-row items-center justify-between pb-2'>
          <CardTitle className={`text-sm font-medium ${net_profit >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>
            Net Profit
          </CardTitle>
          <DollarSign className={`h-5 w-5 ${net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`} />
        </CardHeader>
        <CardContent>
          <p className={`text-2xl font-bold ${net_profit >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>
            {currency} {net_profit.toLocaleString()}
          </p>
        </CardContent>
      </Card>

      <Card className='border-purple-200 bg-purple-50/40'>
        <CardHeader className='flex flex-row items-center justify-between pb-2'>
          <CardTitle className='text-sm font-medium text-purple-700'>Cash Balance</CardTitle>
          <Wallet className='h-5 w-5 text-purple-600' />
        </CardHeader>
        <CardContent>
          <p className='text-2xl font-bold text-purple-700'>
            {currency} {cash_balance.toLocaleString()}
          </p>
        </CardContent>
      </Card>
    </div>
  );
};
