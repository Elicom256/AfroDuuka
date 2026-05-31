import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useSalesByProductQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const SalesByProductReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useSalesByProductQuery(period);

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

      {!data || !data.products ? (
        <div className='text-sm text-muted-foreground'>No sales data</div>
      ) : (
        <div className='space-y-2'>
          {data.products.map((prod: any) => (
            <div key={prod.id ?? prod.sku} className='flex justify-between rounded bg-muted p-2'>
              <div className='text-sm'>{prod.name || prod.product}</div>
              <div className='text-sm font-medium'>{prod.sales ?? prod.quantity ?? '-'}</div>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default SalesByProductReport;
