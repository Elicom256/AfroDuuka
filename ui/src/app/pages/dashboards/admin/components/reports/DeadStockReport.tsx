import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useDeadStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const DeadStockReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useDeadStockQuery(period);

  const deadStockData = data?.data;
  const products = deadStockData?.products || [];

  const formatCurrency = (value: string | number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
    }).format(Number(value));
  };

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Never';
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(new Date(dateString));
  };

  return (
    <ReportCard title="Dead Stock" loading={isLoading}>
      <div className="flex items-center gap-3 mb-6">
        <label className="text-sm text-muted-foreground">Period:</label>
        <select
          className="rounded border px-3 py-1.5 text-sm bg-background"
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

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-lg p-5">
          <p className="text-amber-600 dark:text-amber-400 text-sm font-medium">Dead Stock Items</p>
          <p className="text-4xl font-bold text-amber-700 dark:text-amber-300 mt-1">
            {deadStockData?.dead_stock_count || 0}
          </p>
        </div>

        <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-lg p-5">
          <p className="text-amber-600 dark:text-amber-400 text-sm font-medium">Total Dead Stock Value</p>
          <p className="text-4xl font-bold text-amber-700 dark:text-amber-300 mt-1">
            {formatCurrency(deadStockData?.dead_stock_value || 0)}
          </p>
        </div>
      </div>

      {/* Products List */}
      {products.length === 0 ? (
        <div className="text-center py-12 text-muted-foreground">
          No dead stock items found for this period.
        </div>
      ) : (
        <div className="space-y-3">
          {products.map((item: any) => (
            <div
              key={item.id}
              className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border bg-card hover:bg-muted/50 transition-all"
            >
              <div className="flex-1">
                <p className="font-semibold text-base">{item.name}</p>
                <div className="flex items-center gap-4 text-sm text-muted-foreground mt-1">
                  <span>Quantity: <strong className="text-foreground">{item.quantity}</strong></span>
                  {item.last_sold_at ? (
                    <span>Last Sold: {formatDate(item.last_sold_at)}</span>
                  ) : (
                    <span className="text-amber-600 dark:text-amber-400 font-medium">Never Sold</span>
                  )}
                </div>
              </div>

              <div className="text-right">
                <p className="font-semibold text-lg">
                  {formatCurrency(item.cost_price)}
                </p>
                <p className="text-xs text-muted-foreground">Cost Value</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default DeadStockReport;