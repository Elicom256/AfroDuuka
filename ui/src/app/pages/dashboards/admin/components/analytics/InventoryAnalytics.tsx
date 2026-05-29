import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Doughnut } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Package } from 'lucide-react';
import {
  useBranchProductAnalyticsQuery,
  useBranchProductsQuery,
} from '@/app/store/features/branch/products/branchProductsQuery';

ChartJS.register(ArcElement, Tooltip, Legend);

export const InventoryAnalytics = () => {
  const { data, isLoading, isError } = useBranchProductsQuery();
  const { data: testdata, error } = useBranchProductAnalyticsQuery();
  console.log('useBranchProductAnalyticsQuery==>', testdata ?? error);
  const products = data?.data || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Package className='h-6 w-6' />
            Inventory Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Skeleton className='h-64 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError || !products.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Package className='h-6 w-6' />
            Inventory Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-sm text-muted-foreground'>No inventory data available</p>
        </CardContent>
      </Card>
    );
  }

  const totalItems = products.reduce((sum: number, product: any) => sum + (product.quantity || product.stock || 0), 0);
  const lowStockCount = products.filter((p: any) => (p.quantity || p.stock || 0) < 10).length;
  const totalValue = products.reduce(
    (sum: number, product: any) =>
      sum + (product.quantity || product.stock || 0) * (product.price || product.unit_price || 0),
    0,
  );

  // Top products by stock
  const topProducts = products
    .sort((a: any, b: any) => (b.quantity || b.stock || 0) - (a.quantity || a.stock || 0))
    .slice(0, 5);

  const chartData = {
    labels: topProducts.map((p: any) => p.product_name || p.name),
    datasets: [
      {
        label: 'Stock Quantity',
        data: topProducts.map((p: any) => p.quantity || p.stock || 0),
        backgroundColor: [
          'rgba(75, 192, 192, 0.8)',
          'rgba(54, 162, 235, 0.8)',
          'rgba(255, 206, 86, 0.8)',
          'rgba(75, 192, 192, 0.8)',
          'rgba(153, 102, 255, 0.8)',
        ],
        borderColor: [
          'rgba(75, 192, 192, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 206, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)',
        ],
        borderWidth: 2,
      },
    ],
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Package className='h-6 w-6' />
          Inventory Analytics
        </CardTitle>
        <CardDescription>Stock levels and product distribution</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid gap-2 sm:grid-cols-3'>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Total Items</p>
            <p className='text-xl font-semibold'>{totalItems.toLocaleString()}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Low Stock</p>
            <p className='text-xl font-semibold text-orange-600'>{lowStockCount}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Inventory Value</p>
            <p className='text-xl font-semibold'>
              KSH {totalValue.toLocaleString('en-US', { maximumFractionDigits: 0 })}
            </p>
          </div>
        </div>
        <div className='h-64 w-full'>
          <Doughnut data={chartData} options={{ responsive: true, maintainAspectRatio: false }} />
        </div>
      </CardContent>
    </Card>
  );
};
