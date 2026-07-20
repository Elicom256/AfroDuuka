import { useState } from 'react';
import { useGetFinanceDashboardQuery, useCreateFinanceAdjustmentMutation } from '@/app/store/features/finance/financeQuery';
import { useGetCashFlowsQuery } from '@/app/store/features/business/admin/cashFlowQuery';
import { FinanceSummaryCards } from '../components/finance/FinanceSummaryCards';
import { CashFlowTable } from '../components/finances/CashFlowTable';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Button } from '@/components/ui/button';
import { useNavigate } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';

export const AdminFinancesPage = () => {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const { data: dashboardData, isLoading: dashboardLoading } = useGetFinanceDashboardQuery();
  const { data: cashFlowData, isLoading: cashFlowLoading } = useGetCashFlowsQuery(1);

  const dashboard = dashboardData?.data;
  const cashFlows = cashFlowData?.data?.data ?? [];
  const currentPage = cashFlowData?.data?.current_page ?? 1;
  const totalPages = cashFlowData?.data?.last_page ?? 1;

  if (dashboardLoading || cashFlowLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Financials</h1>
        <p className='text-muted-foreground'>Real-time financial overview of your business</p>
      </div>

      <FinanceSummaryCards
        total_revenue={Number(dashboard?.total_revenue ?? 0)}
        total_expenses={Number(dashboard?.total_expenses ?? 0)}
        net_profit={Number(dashboard?.net_profit ?? 0)}
        cash_balance={Number(dashboard?.cash_balance ?? 0)}
      />

      <div className='relative'>
        <CashFlowTable
          records={cashFlows}
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={setPage}
        />
        <div className='flex justify-end mt-4'>
          <Button variant='outline' onClick={() => navigate('/admin/finance/transactions')}>
            View All Transactions
            <ArrowRight className='ml-2 h-4 w-4' />
          </Button>
        </div>
      </div>
    </div>
  );
};
