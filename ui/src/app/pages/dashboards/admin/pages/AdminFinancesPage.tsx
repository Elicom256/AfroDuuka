import { useState } from 'react';
import { CashFlowAnalytics } from '../components/analytics/CashFlowAnalytics';
import { CashFlowTable } from '../components/finances/CashFlowTable';
import { useGetCashFlowsQuery } from '@/app/store/features/business/admin/cashFlowQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const AdminFinancesPage = () => {
  const [page, setPage] = useState(1);
  const { data, isLoading } = useGetCashFlowsQuery(page);

  const cashFlows = data?.data?.data ?? [];
  const currentPage = data?.data?.current_page ?? 1;
  const totalPages = data?.data?.last_page ?? 1;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Admin finances</h1>
        <p className='text-muted-foreground'>
          Review business cash flow, revenue trends, and expense performance in one place.
        </p>
      </div>

      <CashFlowAnalytics />
      <CashFlowTable
        records={cashFlows}
        currentPage={currentPage}
        totalPages={totalPages}
        onPageChange={setPage}
      />
    </div>
  );
};
