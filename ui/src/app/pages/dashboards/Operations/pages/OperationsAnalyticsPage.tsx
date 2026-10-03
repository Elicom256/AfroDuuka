import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useProductAnalyticsQuery, useProductRestockingQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useLowStockQuery, useOutOfStockQuery, useStockSummaryQuery, useInventoryValuationQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Bar, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { TrendingUp, TrendingDown, Package, DollarSign, AlertTriangle, BarChart3 } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler);

const chartColors = {
  blue: 'rgba(59, 130, 246, 0.8)',
  blueLight: 'rgba(59, 130, 246, 0.2)',
  green: 'rgba(34, 197, 94, 0.8)',
  greenLight: 'rgba(34, 197, 94, 0.2)',
  amber: 'rgba(245, 158, 11, 0.8)',
  red: 'rgba(239, 68, 68, 0.8)',
  purple: 'rgba(168, 85, 247, 0.8)',
  teal: 'rgba(20, 184, 166, 0.8)',
};

const baseOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
};

export const OperationsAnalyticsPage = () => {
  const { currency } = useCurrency();
  const period = '30';

  const { data: analytics, isLoading: analyticsLoading } = useProductAnalyticsQuery();
  const { data: restocking, isLoading: restockingLoading } = useProductRestockingQuery();
  const { data: lowStock, isLoading: lowLoading } = useLowStockQuery(period);
  const { data: outOfStock, isLoading: outLoading } = useOutOfStockQuery(period);
  const { data: stockSummary, isLoading: stockLoading } = useStockSummaryQuery(period);
  const { data: valuation, isLoading: valuationLoading } = useInventoryValuationQuery(period);

  const isLoading = analyticsLoading || restockingLoading || lowLoading || outLoading || stockLoading || valuationLoading;

  if (isLoading) return <PageLoadingState />;

  const statusBreakdown = analytics?.data?.statusBreakdown ?? {};
  const lowItems = lowStock?.data ?? [];
  const outItems = outOfStock?.data ?? [];
  const stockData = stockSummary?.data ?? {};
  const valuationData = valuation?.data ?? {};
  const restockData = restocking?.data ?? {};

  const doughnutData = {
    labels: ['In Stock', 'Low Stock', 'Out of Stock'],
    datasets: [{
      data: [
        statusBreakdown.in_stock ?? 0,
        statusBreakdown.low_stock ?? 0,
        statusBreakdown.out_of_stock ?? 0,
      ],
      backgroundColor: [chartColors.green, chartColors.amber, chartColors.red],
      borderWidth: 0,
    }],
  };

  const stockMovementData = {
    labels: ['In', 'Out', 'Adjustments'],
    datasets: [{
      label: 'Stock Movement',
      data: [
        stockData.total_in ?? 0,
        stockData.total_out ?? 0,
        stockData.total_adjustments ?? 0,
      ],
      backgroundColor: [chartColors.green, chartColors.red, chartColors.purple],
      borderRadius: 8,
    }],
  };

  const topProducts = (restockData.predictions ?? [])
    .filter((p: any) => p.total_sold_30_days > 0)
    .sort((a: any, b: any) => b.total_sold_30_days - a.total_sold_30_days)
    .slice(0, 5);

  const maxSold = topProducts.length > 0 ? Math.max(...topProducts.map((p: any) => p.total_sold_30_days)) : 1;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Analytics</h1>
        <p className='text-muted-foreground'>View performance metrics for your branch</p>
      </div>

      <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-4'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-blue-500/10 p-2.5'>
              <Package className='h-5 w-5 text-blue-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Products</p>
              <p className='text-xl font-semibold'>{analytics?.data?.total_products ?? 0}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-green-500/10 p-2.5'>
              <DollarSign className='h-5 w-5 text-green-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Stock Value</p>
              <p className='text-xl font-semibold'>{currency} {(valuationData.total_value ?? 0).toLocaleString()}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-amber-500/10 p-2.5'>
              <AlertTriangle className='h-5 w-5 text-amber-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Low Stock Items</p>
              <p className='text-xl font-semibold'>{restockData.low_stock_count ?? 0}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-red-500/10 p-2.5'>
              <TrendingDown className='h-5 w-5 text-red-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Out of Stock</p>
              <p className='text-xl font-semibold'>{restockData.at_risk_count ?? 0}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <BarChart3 className='h-4 w-4' />
              Stock Status Breakdown
            </CardTitle>
            <CardDescription>Distribution of products by stock level</CardDescription>
          </CardHeader>
          <CardContent>
            <div className='h-64'>
              <Doughnut data={doughnutData} options={{ ...baseOptions, plugins: { legend: { display: true, position: 'bottom' } } }} />
            </div>
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <TrendingUp className='h-4 w-4' />
              Stock Movement
            </CardTitle>
            <CardDescription>Stock in, out, and adjustments</CardDescription>
          </CardHeader>
          <CardContent>
            <div className='h-64'>
              <Bar data={stockMovementData} options={baseOptions} />
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-4'>
        <CardHeader>
          <CardTitle>Top Selling Products (30 days)</CardTitle>
          <CardDescription>Products with highest sales velocity</CardDescription>
        </CardHeader>
        <CardContent>
          {topProducts.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No sales data available for this period.</p>
          ) : (
            <div className='space-y-3'>
              {topProducts.map((product: any) => (
                <div key={product.id} className='space-y-1'>
                  <div className='flex items-center justify-between text-sm'>
                    <span className='font-medium'>{product.name}</span>
                    <span className='text-muted-foreground'>{product.total_sold_30_days} sold</span>
                  </div>
                  <div className='h-2 w-full overflow-hidden rounded-full bg-muted'>
                    <div
                      className='h-full rounded-full bg-blue-500 transition-all'
                      style={{ width: `${(product.total_sold_30_days / maxSold) * 100}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Low Stock Items</CardTitle>
            <CardDescription>Products below reorder level</CardDescription>
          </CardHeader>
          <CardContent>
            {lowItems.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No low stock items.</p>
            ) : (
              <div className='space-y-2'>
                {lowItems.slice(0, 5).map((item: any) => (
                  <div key={item.id} className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2 text-sm'>
                    <span>{item.name}</span>
                    <span className='font-medium text-amber-500'>{item.quantity} left</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Out of Stock Items</CardTitle>
            <CardDescription>Products with zero quantity</CardDescription>
          </CardHeader>
          <CardContent>
            {outItems.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No out of stock items.</p>
            ) : (
              <div className='space-y-2'>
                {outItems.slice(0, 5).map((item: any) => (
                  <div key={item.id} className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2 text-sm'>
                    <span>{item.name}</span>
                    <span className='font-medium text-red-500'>Out of stock</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
