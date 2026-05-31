import { useState } from 'react';
import ReportCard from './ReportCard';
import { periods } from '../periodHelper';
import { useStockSummaryQuery } from '@/app/store/features/branch/reports/branchReportsQuery';

export const StockSummaryReport = () => {
  const [period, setPeriod] = useState<string>(periods[0].value);
  const { data, isLoading } = useStockSummaryQuery(period);

  const summary = data?.data;

  // Format currency
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
    }).format(value);
  };

  if (!summary) {
    return (
      <ReportCard title="Stock Summary" loading={isLoading}>
        <div className="text-sm text-muted-foreground">No data available</div>
      </ReportCard>
    );
  }

  const products = summary.products?.data || [];

  return (
    <ReportCard title="Stock Summary" loading={isLoading}>
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

      {/* Key Metrics */}
      <div className="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Total Products</p>
          <p className="text-3xl font-semibold mt-1">{summary.total_products}</p>
        </div>

        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Active Products</p>
          <p className="text-3xl font-semibold mt-1 text-green-600">
            {summary.active_products}
          </p>
        </div>

        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Inactive Products</p>
          <p className="text-3xl font-semibold mt-1 text-red-600">
            {summary.inactive_products}
          </p>
        </div>

        <div className="bg-muted/50 rounded-lg p-4">
          <p className="text-sm text-muted-foreground">Total Stock Qty</p>
          <p className="text-3xl font-semibold mt-1">{summary.total_stock_quantity}</p>
        </div>

        <div className="bg-muted/50 rounded-lg p-4 md:col-span-2">
          <p className="text-sm text-muted-foreground">Total Inventory Value</p>
          <p className="text-3xl font-semibold mt-1 text-primary">
            {formatCurrency(summary.total_inventory_value)}
          </p>
        </div>
      </div>

      {/* Products List */}
      <div className="mt-6">
        <h3 className="text-lg font-semibold mb-4">Products ({products.length})</h3>
        
        <div className="space-y-3">
          {products.map((product: any, idx: number) => (
            <div
              key={idx}
              className="flex justify-between items-center p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
            >
              <div>
                <p className="font-medium">{product.name || product.product_name}</p>
                <p className="text-sm text-muted-foreground">
                  {product.sku || product.code}
                </p>
              </div>

              <div className="text-right">
                <p className="font-semibold">
                  {product.stock_quantity || product.quantity} units
                </p>
                <p className="text-sm text-muted-foreground">
                  {formatCurrency(product.stock_value || product.value || 0)}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </ReportCard>
  );
};

export default StockSummaryReport;