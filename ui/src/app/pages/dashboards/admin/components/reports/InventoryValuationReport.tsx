import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useInventoryValuationQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const InventoryValuationReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useInventoryValuationQuery(period);

  const valuation = data?.data;
  const categories = valuation?.grouped_by_category || [];
  const statuses = valuation?.grouped_by_status || [];

  const formatCurrency = (value: string | number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
    }).format(Number(value));
  };

  return (
    <ReportCard title="Inventory Valuation" loading={isLoading}>
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

      {!valuation ? (
        <div className="text-center py-12 text-muted-foreground">
          No valuation data available
        </div>
      ) : (
        <div className="space-y-8">
          {/* Overall Summary */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-card border rounded-xl p-5">
              <p className="text-sm text-muted-foreground">Total Inventory Value</p>
              <p className="text-3xl font-bold mt-2 text-primary">
                {formatCurrency(valuation.total_inventory_value)}
              </p>
            </div>

            <div className="bg-card border rounded-xl p-5">
              <p className="text-sm text-muted-foreground">Total Quantity</p>
              <p className="text-3xl font-bold mt-2">{valuation.total_quantity}</p>
            </div>

            <div className="bg-card border rounded-xl p-5">
              <p className="text-sm text-muted-foreground">Average Cost Price</p>
              <p className="text-3xl font-bold mt-2">
                {formatCurrency(valuation.average_cost_price)}
              </p>
            </div>
          </div>

          {/* Breakdown by Status */}
          <div>
            <h3 className="text-lg font-semibold mb-4">By Status</h3>
            <div className="space-y-3">
              {statuses.map((status: any, idx: number) => (
                <div
                  key={idx}
                  className="flex flex-col sm:flex-row sm:items-center justify-between p-5 rounded-xl border bg-card"
                >
                  <div>
                    <p className="font-semibold capitalize text-base">{status.status}</p>
                    <p className="text-sm text-muted-foreground mt-1">
                      {status.total_quantity} units
                    </p>
                  </div>
                  <div className="text-right sm:text-left mt-3 sm:mt-0">
                    <p className="font-semibold text-lg">
                      {formatCurrency(status.total_inventory_value)}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Avg: {formatCurrency(status.average_cost_price)}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Breakdown by Category */}
          <div>
            <h3 className="text-lg font-semibold mb-4">By Category</h3>
            <div className="space-y-3">
              {categories.length === 0 ? (
                <p className="text-muted-foreground text-sm">No category data available</p>
              ) : (
                categories.map((cat: any, idx: number) => (
                  <div
                    key={idx}
                    className="flex flex-col sm:flex-row sm:items-center justify-between p-5 rounded-xl border bg-card"
                  >
                    <div>
                      <p className="font-semibold text-base capitalize">{cat.category_name}</p>
                      <p className="text-sm text-muted-foreground mt-1">
                        {cat.total_quantity} units
                      </p>
                    </div>
                    <div className="text-right sm:text-left mt-3 sm:mt-0">
                      <p className="font-semibold text-lg">
                        {formatCurrency(cat.total_inventory_value)}
                      </p>
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

export default InventoryValuationReport;