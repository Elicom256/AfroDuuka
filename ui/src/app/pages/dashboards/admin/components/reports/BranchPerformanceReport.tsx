import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useBranchPerformanceQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';

export const BranchPerformanceReport = () => {
  const { currency } = useCurrency();
  const [period, setPeriod] = useState<string>(periods[0].value);
  const [selectedBranchId, setSelectedBranchId] = useState<string | null>(null);

  const { data: branchesData } = useBranchesQuery();
  const branches = branchesData?.branches || [];
  const defaultBranchId = branches.length > 0 ? branches[0].id : null;

  const { data, isLoading } = useBranchPerformanceQuery({
    id: selectedBranchId || defaultBranchId,
    period,
  });

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
    }).format(value);
  };

  const report = data?.data;
  const summary = report?.summary;
  const branchRows = report?.branches || [];

  return (
    <ReportCard title='Branch Performance' loading={isLoading}>
      <div className='flex flex-wrap gap-4 mb-6'>
        <div className='flex items-center gap-3'>
          <label className='text-sm text-muted-foreground'>Period:</label>
          <select
            className='rounded border px-3 py-1.5 text-sm bg-background'
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
          >
            {periods.map((p) => (
              <option key={p.value} value={p.value}>
                {p.label}
              </option>
            ))}
          </select>
        </div>

        <div className='flex items-center gap-3'>
          <label className='text-sm text-muted-foreground'>Branch:</label>
          <select
            className='rounded border px-3 py-1.5 text-sm bg-background min-w-[180px]'
            value={selectedBranchId || defaultBranchId || ''}
            onChange={(e) => setSelectedBranchId(e.target.value)}
          >
            {branches.map((branch: any) => (
              <option key={branch.id} value={branch.id}>
                {branch.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {!summary ? (
        <div className='text-center py-12 text-muted-foreground'>
          No performance data available for the selected branch and period.
        </div>
      ) : (
        <div className='space-y-6'>
          <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
            <div className='bg-card border rounded-xl p-5'>
              <p className='text-sm text-muted-foreground'>Total Revenue</p>
              <p className='text-3xl font-semibold mt-2 text-emerald-600'>
                {formatCurrency(summary.total_company_revenue)}
              </p>
            </div>
            <div className='bg-card border rounded-xl p-5'>
              <p className='text-sm text-muted-foreground'>Total Expenses</p>
              <p className='text-3xl font-semibold mt-2 text-red-600'>
                {formatCurrency(summary.total_company_expenses)}
              </p>
            </div>
            <div className='bg-card border rounded-xl p-5'>
              <p className='text-sm text-muted-foreground'>Net Profit</p>
              <p className={`text-3xl font-semibold mt-2 ${summary.total_company_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                {formatCurrency(summary.total_company_profit)}
              </p>
            </div>
          </div>

          {summary.best_performing_branch && (
            <div className='bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900 rounded-xl p-4'>
              <p className='text-sm text-emerald-600 font-medium'>Best Performing Branch</p>
              <p className='text-lg font-bold mt-1'>{summary.best_performing_branch.branch_name}</p>
              <p className='text-sm text-muted-foreground'>
                Revenue: {formatCurrency(summary.best_performing_branch.total_revenue)} | 
                Profit: {formatCurrency(summary.best_performing_branch.net_profit)}
              </p>
            </div>
          )}

          {branchRows.length > 1 && (
            <div>
              <h3 className='text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3'>
                All Branches
              </h3>
              <div className='space-y-2'>
                {branchRows.map((branch: any) => (
                  <div
                    key={branch.branch_id}
                    className='flex justify-between items-center p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors'
                  >
                    <div>
                      <p className='font-medium'>{branch.branch_name}</p>
                      <p className='text-xs text-muted-foreground'>{branch.transaction_count} transactions</p>
                    </div>
                    <div className='text-right'>
                      <p className='font-semibold'>{formatCurrency(branch.total_revenue)}</p>
                      <p className={`text-xs ${branch.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                        {branch.net_profit >= 0 ? '+' : ''}{formatCurrency(branch.net_profit)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </ReportCard>
  );
};

export default BranchPerformanceReport;
