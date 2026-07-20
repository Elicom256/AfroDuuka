import { DollarSign, TrendingUp, TrendingDown } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useGetFinanceDashboardQuery } from '@/app/store/features/finance/financeQuery';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ManagerFinancesPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useGetFinanceDashboardQuery();
  const finances = data?.data ?? {};
  const revenue = Number(finances.total_revenue ?? 0);
  const expenses = Number(finances.total_expenses ?? 0);
  const profit = Number(finances.net_profit ?? 0);

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Finances' description='Review branch revenue, expenses, and operating profit.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard
            title='Revenue'
            value={`${currency} ${revenue.toLocaleString()}`}
            icon={<DollarSign className='h-5 w-5' />}
          />
          <SectionCard
            title='Expenses'
            value={`${currency} ${expenses.toLocaleString()}`}
            icon={<TrendingDown className='h-5 w-5' />}
          />
          <SectionCard
            title='Profit'
            value={`${currency} ${profit.toLocaleString()}`}
            icon={<TrendingUp className='h-5 w-5' />}
          />
        </div>
        <div className='rounded-3xl border border-border/70 bg-background p-4'>
          <p className='font-medium'>Last update</p>
          <p className='text-sm text-muted-foreground'>
            Based on branch financial activity and the latest revenue feed.
          </p>
        </div>
      </ManagerPageShell>
    </div>
  );
};
