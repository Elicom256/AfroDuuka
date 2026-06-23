import { DollarSign, TrendingUp } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchFinancesQuery } from '@/app/store/features/branch';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ManagerFinancesPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useBranchFinancesQuery();
  const finances = data?.data ?? data ?? {};
  const revenue = Number(finances.revenue ?? finances.totalRevenue ?? finances.sales ?? 0);
  const expenses = Number(finances.expenses ?? finances.totalExpenses ?? 0);
  const profit = revenue - expenses;

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
            icon={<DollarSign className='h-5 w-5' />}
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
