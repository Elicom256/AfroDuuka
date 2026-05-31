import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useLowStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const LowStockReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useLowStockQuery(period);

  const lowStockData = data?.data;
  const products = lowStockData?.products || [];

  const formatCurrency = (value: string | number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
    }).format(Number(value));
  };

  return (
    <ReportCard title='Low Stock' loading={isLoading}>
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
          <p className='text-red-600 dark:text-red-400 text-sm font-medium'>Low Stock Items</p>
          <p className='text-4xl font-bold text-red-700 dark:text-red-300 mt-1'>{lowStockData?.low_stock_count || 0}</p>
        </div>
      </div>

      {/* Products List */}
      {products.length === 0 ? (
        <div className='text-center py-12 text-muted-foreground'>No low stock items found for this period.</div>
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
                    Reorder Level: <strong className='text-foreground'>{item.reorder_level}</strong>
                  </span>
                  <span>
                    Status: <span className='capitalize'>{item.status}</span>
                  </span>
                </div>
              </div>

              <div className='text-right sm:text-center'>
                <p className='text-2xl font-bold text-red-600'>{item.quantity}</p>
                <p className='text-xs text-muted-foreground'>Current Stock</p>
              </div>

              <div className='text-right'>
                <p className='font-medium'>{formatCurrency(item.cost_price)}</p>
                <p className='text-xs text-muted-foreground'>Cost Price</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default LowStockReport;
