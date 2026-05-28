import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { DollarSign } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

export const SalesAnalytics = () => {
  const { data, isLoading, isError } = useSalesQuery();
  const sales = data?.data || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <DollarSign className='h-6 w-6' />
            Sales Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Skeleton className='h-64 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError || !sales.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <DollarSign className='h-6 w-6' />
            Sales Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-sm text-muted-foreground'>No sales data available</p>
        </CardContent>
      </Card>
    );
  }

  const totalSales = sales.reduce((sum: number, sale: any) => sum + (sale.total_amount || 0), 0);
  const avgSale = sales.length > 0 ? totalSales / sales.length : 0;

  // Group sales by date for chart
  const salesByDate: Record<string, number> = {};
  sales.forEach((sale: any) => {
    const date = new Date(sale.created_at || sale.date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
    });
    salesByDate[date] = (salesByDate[date] || 0) + (sale.total_amount || 0);
  });

  const chartData = {
    labels: Object.keys(salesByDate).slice(-7), // Last 7 days
    datasets: [
      {
        label: 'Daily Sales',
        data: Object.values(salesByDate).slice(-7),
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        tension: 0.4,
      },
    ],
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <DollarSign className='h-6 w-6' />
          Sales Analytics
        </CardTitle>
        <CardDescription>Last 7 days of sales</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid gap-2 sm:grid-cols-2'>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Total Sales</p>
            <p className='text-xl font-semibold'>KSH {totalSales.toLocaleString()}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Avg Sale</p>
            <p className='text-xl font-semibold'>KSH {avgSale.toLocaleString('en-US', { maximumFractionDigits: 0 })}</p>
          </div>
        </div>
        <div className='h-64 w-full'>
          <Line data={chartData} options={{ responsive: true, maintainAspectRatio: false }} />
        </div>
      </CardContent>
    </Card>
  );
};
