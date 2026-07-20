import { useState } from 'react';
import { useGetFinanceDashboardQuery } from '@/app/store/features/finance/financeQuery';
import { FinanceSummaryCards } from '../components/finance/FinanceSummaryCards';
import { CashFlowAnalytics } from '../components/analytics/CashFlowAnalytics';
import { CashFlowTable } from '../components/finances/CashFlowTable';
import { useGetCashFlowsQuery } from '@/app/store/features/business/admin/cashFlowQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const AdminFinanceCashFlowPage = () => {
  const [page, setPage] = useState(1);
  const { data: dashboardData, isLoading: dashboardLoading } = useGetFinanceDashboardQuery();
  const { data, isLoading: cfLoading } = useGetCashFlowsQuery(page);

  const dashboard = dashboardData?.data;
  const cashFlows = data?.data?.data ?? [];
  const currentPage = data?.data?.current_page ?? 1;
  const totalPages = data?.data?.last_page ?? 1;

  if (dashboardLoading || cfLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Cash Flow</h1>
        <p className='text-muted-foreground'>Track all cash movements across your business.</p>
      </div>
      <FinanceSummaryCards
        total_revenue={Number(dashboard?.total_revenue ?? 0)}
        total_expenses={Number(dashboard?.total_expenses ?? 0)}
        net_profit={Number(dashboard?.net_profit ?? 0)}
        cash_balance={Number(dashboard?.cash_balance ?? 0)}
      />
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
