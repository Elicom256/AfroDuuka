import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useSalesByProductQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';

export const SalesByProductReport = () => {
  const { currency } = useCurrency();
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useSalesByProductQuery(period);

  const reportData = data?.data;
  const topProducts = reportData?.top_products || [];

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
    }).format(value);
  };

  return (
    <ReportCard title='Sales By Product' loading={isLoading}>
      <div className='flex items-center gap-3 mb-4'>
        <label className='text-sm text-muted-foreground'>Period:</label>
        <select className='rounded border px-2 py-1 text-sm' value={period} onChange={(e) => setPeriod(e.target.value)}>
          {periods.map((p) => (
            <option key={p.value} value={p.value}>
              {p.label}
            </option>
          ))}
        </select>
      </div>

      {topProducts.length === 0 ? (
        <div className='text-sm text-muted-foreground'>No sales data for this period</div>
      ) : (
        <div className='space-y-2'>
          {topProducts.map((prod: any) => (
            <div
              key={prod.product_id}
              className='flex justify-between items-center p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors'
            >
              <div className='text-sm font-medium'>{prod.product_name}</div>
              <div className='text-right'>
                <p className='font-semibold'>{prod.quantity_sold} sold</p>
                <p className='text-xs text-muted-foreground'>{formatCurrency(prod.total_revenue)}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default SalesByProductReport;
