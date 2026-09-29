import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useLowStockQuery, useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Bar, Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { TrendingUp, DollarSign, ShoppingCart, Package, AlertTriangle, Clock } from 'lucide-react';
import { format, subDays, isAfter } from 'date-fns';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler);

const chartColors = {
  blue: 'rgba(59, 130, 246, 0.8)',
  blueLight: 'rgba(59, 130, 246, 0.2)',
  green: 'rgba(34, 197, 94, 0.8)',
  greenLight: 'rgba(34, 197, 94, 0.2)',
  amber: 'rgba(245, 158, 11, 0.8)',
  red: 'rgba(239, 68, 68, 0.8)',
  purple: 'rgba(168, 85, 247, 0.8)',
};

const baseOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
};

export const StaffSalesOverviewPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useSalesQuery();
  const { data: productData } = useProductsQuery();
  const { data: lowStock } = useLowStockQuery('30');
  const { data: outOfStock } = useOutOfStockQuery('30');

  if (isLoading) return <PageLoadingState />;

  const sales = data?.sales ?? data ?? [];
  const products = productData?.products ?? [];
  const lowItems = lowStock?.data ?? [];
  const outItems = outOfStock?.data ?? [];

  const totalRevenue = sales.reduce((sum: number, sale: any) => sum + Number(sale.total_amount ?? 0), 0);
  const totalOrders = sales.length;
  const totalItemsSold = sales.reduce((sum: number, sale: any) => {
    return sum + (sale.sale_items ?? []).reduce((acc: number, item: any) => acc + Number(item.quantity ?? 0), 0);
  }, 0);
  const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

  const last7Days = Array.from({ length: 7 }, (_, i) => {
    const date = subDays(new Date(), 6 - i);
    const dayLabel = format(date, 'EEE');
    const daySales = sales.filter((sale: any) => {
      const saleDate = new Date(sale.created_at);
      return isAfter(saleDate, subDays(new Date(), 7)) && format(saleDate, 'yyyy-MM-dd') === format(date, 'yyyy-MM-dd');
    });
    const revenue = daySales.reduce((sum: number, sale: any) => sum + Number(sale.total_amount ?? 0), 0);
    return { label: dayLabel, revenue, orders: daySales.length };
  });

  const salesTrendData = {
    labels: last7Days.map((d) => d.label),
    datasets: [{
      label: 'Revenue',
      data: last7Days.map((d) => d.revenue),
      borderColor: chartColors.blue,
      backgroundColor: chartColors.blueLight,
      fill: true,
      tension: 0.4,
    }],
  };

  const ordersBarData = {
    labels: last7Days.map((d) => d.label),
    datasets: [{
      label: 'Orders',
      data: last7Days.map((d) => d.orders),
      backgroundColor: chartColors.green,
      borderRadius: 8,
    }],
  };

  const topProducts = products
    .map((p: any) => {
      const productSales = sales.flatMap((sale: any) => sale.sale_items ?? [])
        .filter((item: any) => item.product_id === p.id);
      const totalSold = productSales.reduce((sum: number, item: any) => sum + Number(item.quantity ?? 0), 0);
      return { ...p, totalSold };
    })
    .filter((p: any) => p.totalSold > 0)
    .sort((a: any, b: any) => b.totalSold - a.totalSold)
    .slice(0, 5);

  const maxSold = topProducts.length > 0 ? Math.max(...topProducts.map((p: any) => p.totalSold)) : 1;

  const recentSales = [...sales]
    .sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Sales Overview</h1>
        <p className='text-muted-foreground'>View sales flow and trends</p>
      </div>

      <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-4'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-green-500/10 p-2.5'>
              <DollarSign className='h-5 w-5 text-green-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Revenue</p>
              <p className='text-xl font-semibold'>{currency} {totalRevenue.toLocaleString()}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-blue-500/10 p-2.5'>
              <ShoppingCart className='h-5 w-5 text-blue-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Orders</p>
              <p className='text-xl font-semibold'>{totalOrders}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-purple-500/10 p-2.5'>
              <Package className='h-5 w-5 text-purple-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Items Sold</p>
              <p className='text-xl font-semibold'>{totalItemsSold}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-amber-500/10 p-2.5'>
              <TrendingUp className='h-5 w-5 text-amber-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Avg Order Value</p>
              <p className='text-xl font-semibold'>{currency} {avgOrderValue.toLocaleString()}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <TrendingUp className='h-4 w-4' />
              Revenue Trend (7 days)
            </CardTitle>
            <CardDescription>Daily revenue over the last week</CardDescription>
          </CardHeader>
          <CardContent>
            <div className='h-64'>
              <Line data={salesTrendData} options={baseOptions} />
            </div>
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <ShoppingCart className='h-4 w-4' />
              Daily Orders (7 days)
            </CardTitle>
            <CardDescription>Number of orders per day</CardDescription>
          </CardHeader>
          <CardContent>
            <div className='h-64'>
              <Bar data={ordersBarData} options={baseOptions} />
            </div>
          </CardContent>
        </Card>
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Top Selling Products</CardTitle>
            <CardDescription>Products with highest sales</CardDescription>
          </CardHeader>
          <CardContent>
            {topProducts.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No sales data available.</p>
            ) : (
              <div className='space-y-3'>
                {topProducts.map((product: any) => (
                  <div key={product.id} className='space-y-1'>
                    <div className='flex items-center justify-between text-sm'>
                      <span className='font-medium'>{product.name}</span>
                      <span className='text-muted-foreground'>{product.totalSold} sold</span>
                    </div>
                    <div className='h-2 w-full overflow-hidden rounded-full bg-muted'>
                      <div
                        className='h-full rounded-full bg-green-500 transition-all'
                        style={{ width: `${(product.totalSold / maxSold) * 100}%` }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <Clock className='h-4 w-4' />
              Recent Sales
            </CardTitle>
            <CardDescription>Latest transactions</CardDescription>
          </CardHeader>
          <CardContent>
            {recentSales.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No recent sales.</p>
            ) : (
              <div className='space-y-2'>
                {recentSales.map((sale: any) => (
                  <div key={sale.id} className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2 text-sm'>
                    <div>
                      <p className='font-medium'>Order #{sale.id}</p>
                      <p className='text-xs text-muted-foreground'>{format(new Date(sale.created_at), 'PPp')}</p>
                    </div>
                    <span className='font-medium text-green-500'>{currency} {Number(sale.total_amount ?? 0).toLocaleString()}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <AlertTriangle className='h-4 w-4 text-amber-500' />
              Low Stock Alerts
            </CardTitle>
            <CardDescription>Products below reorder level</CardDescription>
          </CardHeader>
          <CardContent>
            {lowItems.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No low stock items.</p>
            ) : (
              <div className='space-y-2'>
                {lowItems.slice(0, 5).map((item: any) => (
                  <div key={item.id} className='flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm dark:border-amber-900/50 dark:bg-amber-950/20'>
                    <span>{item.name}</span>
                    <span className='font-medium text-amber-600 dark:text-amber-400'>{item.quantity} left</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <Package className='h-4 w-4 text-red-500' />
              Out of Stock
            </CardTitle>
            <CardDescription>Products needing restock</CardDescription>
          </CardHeader>
          <CardContent>
            {outItems.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No out of stock items.</p>
            ) : (
              <div className='space-y-2'>
                {outItems.slice(0, 5).map((item: any) => (
                  <div key={item.id} className='flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm dark:border-red-900/50 dark:bg-red-950/20'>
                    <span>{item.name}</span>
                    <span className='font-medium text-red-600 dark:text-red-400'>Out of stock</span>
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
