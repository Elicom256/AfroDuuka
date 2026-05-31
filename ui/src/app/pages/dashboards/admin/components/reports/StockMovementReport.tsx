import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useStockMovementQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const StockMovementReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useStockMovementQuery(period);

  const movementData = data?.data;
  const trend = movementData?.trend || [];
  const summary = movementData?.summary || {};

  const formatNumber = (num: number) => {
    return num.toLocaleString('en-US');
  };

  return (
    <ReportCard title='Stock Movement' loading={isLoading}>
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

      {!movementData ? (
        <div className='text-center py-12 text-muted-foreground'>No stock movement data available</div>
      ) : (
        <div className='space-y-8'>
          {/* Summary Metrics */}
          <div className='grid grid-cols-1 md:grid-cols-3 gap-4'>
            <div className='bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 rounded-xl p-5'>
              <p className='text-emerald-600 dark:text-emerald-400 text-sm font-medium'>Total Stock In</p>
              <p className='text-3xl font-bold mt-2 text-emerald-700 dark:text-emerald-300'>
                +{formatNumber(summary.total_stock_in || 0)}
              </p>
            </div>

            <div className='bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 rounded-xl p-5'>
              <p className='text-red-600 dark:text-red-400 text-sm font-medium'>Total Stock Out</p>
              <p className='text-3xl font-bold mt-2 text-red-700 dark:text-red-300'>
                -{formatNumber(summary.total_stock_out || 0)}
              </p>
            </div>

            <div
              className={`border rounded-xl p-5 ${
                summary.net_movement && summary.net_movement >= 0
                  ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200'
                  : 'bg-red-50 dark:bg-red-950/30 border-red-200'
              }`}
            >
              <p className='text-sm font-medium'>Net Movement</p>
              <p
                className={`text-3xl font-bold mt-2 ${
                  summary.net_movement && summary.net_movement >= 0
                    ? 'text-emerald-700 dark:text-emerald-300'
                    : 'text-red-700 dark:text-red-300'
                }`}
              >
                {summary.net_movement && summary.net_movement >= 0 ? '+' : ''}
                {formatNumber(summary.net_movement || 0)}
              </p>
            </div>
          </div>

          {/* Daily Trend */}
          <div>
            <h3 className='text-lg font-semibold mb-4'>Daily Trend (Last 7 Days)</h3>

            <div className='space-y-2'>
              {trend.length === 0 ? (
                <div className='text-center py-8 text-muted-foreground'>No trend data available</div>
              ) : (
                trend.map((day: any, idx: number) => (
                  <div
                    key={idx}
                    className='flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border bg-card hover:bg-muted/50 transition-all'
                  >
                    <div className='font-medium'>
                      {new Intl.DateTimeFormat('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                      }).format(new Date(day.date))}
                    </div>

                    <div className='flex gap-6 text-sm'>
                      <div>
                        <span className='text-emerald-600'>In: </span>
                        <span className='font-medium'>{formatNumber(day.stock_in)}</span>
                      </div>
                      <div>
                        <span className='text-red-600'>Out: </span>
                        <span className='font-medium'>{formatNumber(day.stock_out)}</span>
                      </div>
                    </div>

                    <div
                      className={`font-semibold px-4 py-1 rounded-full text-xs w-fit
                          ${
                            day.net_movement >= 0
                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                              : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                          }`}
                    >
                      {day.net_movement >= 0 ? '+' : ''}
                      {formatNumber(day.net_movement)}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}
    </ReportCard>
  );
};

export default StockMovementReport;
