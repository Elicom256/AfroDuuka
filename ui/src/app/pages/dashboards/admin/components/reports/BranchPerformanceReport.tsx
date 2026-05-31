import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useBranchPerformanceQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';

export const BranchPerformanceReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const [selectedBranchId, setSelectedBranchId] = useState<string | null>(null);

  const { data: branchesData } = useBranchesQuery();
  const branches = branchesData?.branches || [];

  // Set default branch on first load
  const defaultBranchId = branches.length > 0 ? branches[0].id : null;

  const { data, isLoading } = useBranchPerformanceQuery({
    id: selectedBranchId || defaultBranchId,
    period,
  });

  const performanceData = data?.data || data; // Adjust based on your API structure
  const metrics = performanceData?.metrics || {};

  return (
    <ReportCard title='Branch Performance' loading={isLoading}>
      {/* Filters */}
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

      {!performanceData || Object.keys(metrics).length === 0 ? (
        <div className='text-center py-12 text-muted-foreground'>
          No performance data available for the selected branch and period.
        </div>
      ) : (
        <div className='space-y-8'>
          {/* Main Metrics Grid */}
          <div className='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4'>
            {Object.entries(metrics).map(([key, value]: [string, any]) => (
              <div key={key} className='bg-card border rounded-xl p-5 hover:shadow-sm transition-all'>
                <p className='text-sm text-muted-foreground capitalize'>{key.replace(/_/g, ' ')}</p>
                <p className='text-3xl font-semibold mt-3'>
                  {typeof value === 'number' ? value.toLocaleString('en-US') : String(value)}
                </p>
              </div>
            ))}
          </div>

          {/* Optional: Add more sections here if your API returns sales, profit, etc. */}
        </div>
      )}
    </ReportCard>
  );
};

export default BranchPerformanceReport;
