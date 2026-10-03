import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

export const OutOfStockReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading, isFetching, isError, refetch } = useOutOfStockQuery(period);

  const outOfStockData = data?.data;
  const products = outOfStockData?.products || [];

  if (isError && !data) {
    return (
      <ReportCard title='Out of Stock' loading={isLoading}>
        <QueryErrorState
          title='Unable to load out-of-stock report'
          description='Stock counts are unavailable because the report request failed.'
          onRetry={refetch}
          retrying={isFetching}
        />
      </ReportCard>
    );
  }

  const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateString));
  };

  return (
    <ReportCard title='Out of Stock' loading={isLoading}>
      {isError && (
        <QueryErrorState
          title='Out-of-stock report may be out of date'
          description='The latest refresh failed. The last loaded report is still shown.'
          onRetry={refetch}
          retrying={isFetching}
        />
      )}
      <div className='flex items-center gap-3 mb-6'>
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

      {/* Summary */}
      <div className='mb-8'>
        <div className='bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 rounded-lg p-5'>
          <p className='text-red-600 dark:text-red-400 text-sm font-medium'>Out of Stock Items</p>
          <p className='text-4xl font-bold text-red-700 dark:text-red-300 mt-1'>
            {outOfStockData?.out_of_stock_count || 0}
          </p>
        </div>
      </div>

      {/* Products List */}
      {products.length === 0 ? (
        <QueryEmptyState
          title='No out-of-stock products'
          description='No products were out of stock during this period.'
        />
      ) : (
        <div className='space-y-3'>
          {products.map((item: any) => (
            <div
              key={item.id}
              className='flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border bg-card hover:bg-muted/50 transition-all'
            >
              <div className='flex-1'>
                <p className='font-semibold text-base'>{item.name}</p>
                <div className='flex items-center gap-4 text-sm text-muted-foreground mt-1'>
                  <span>
                    Status: <span className='capitalize font-medium text-foreground'>{item.status}</span>
                  </span>
                  {item.last_sold_at && (
                    <span>
                      Last Sold: <span className='font-medium'>{formatDate(item.last_sold_at)}</span>
                    </span>
                  )}
                </div>
              </div>

              <div className='px-4 py-2 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded-lg text-center font-medium'>
                OUT OF STOCK
              </div>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default OutOfStockReport;
