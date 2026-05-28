import { usePurchaseAnalyticsQuery, usePurchasesQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { ShoppingCart } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const PurchasesAnalytics = () => {
  const { data, isLoading, isError } = usePurchaseAnalyticsQuery(undefined);
  const purchases = data?.data || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <ShoppingCart className='h-6 w-6' />
            Purchases Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Skeleton className='h-64 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError || !purchases.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <ShoppingCart className='h-6 w-6' />
            Purchases Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-sm text-muted-foreground'>No purchases data available</p>
        </CardContent>
      </Card>
    );
  }

  const totalPurchases = purchases.reduce(
    (sum: number, purchase: any) => sum + (purchase.total_amount || purchase.total || 0),
    0,
  );
  const avgPurchase = purchases.length > 0 ? totalPurchases / purchases.length : 0;

  // Group purchases by date
  const purchasesByDate: Record<string, number> = {};
  purchases.forEach((purchase: any) => {
    const date = new Date(purchase.created_at || purchase.date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
    });
    purchasesByDate[date] = (purchasesByDate[date] || 0) + (purchase.total_amount || purchase.total || 0);
  });

  const chartData = {
    labels: Object.keys(purchasesByDate).slice(-7),
    datasets: [
      {
        label: 'Daily Purchases',
        data: Object.values(purchasesByDate).slice(-7),
        backgroundColor: 'rgba(255, 159, 64, 0.6)',
        borderColor: 'rgba(255, 159, 64, 1)',
        borderWidth: 2,
      },
    ],
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <ShoppingCart className='h-6 w-6' />
          Purchases Analytics
        </CardTitle>
        <CardDescription>Last 7 days of purchases</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid gap-2 sm:grid-cols-2'>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Total Purchases</p>
            <p className='text-xl font-semibold'>KSH {totalPurchases.toLocaleString()}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Avg Purchase</p>
            <p className='text-xl font-semibold'>
              KSH {avgPurchase.toLocaleString('en-US', { maximumFractionDigits: 0 })}
            </p>
          </div>
        </div>
        <div className='h-64 w-full'>
          <Bar data={chartData} options={{ responsive: true, maintainAspectRatio: false }} />
        </div>
      </CardContent>
    </Card>
  );
};
