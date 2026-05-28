import { useState, useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Line } from 'react-chartjs-2';
import { DollarSign, TrendingUp } from 'lucide-react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

// Types
interface SalesTrend {
  date: string;
  amount: number;
  count: number;
}

interface SalesAnalyticsData {
  total_sales: number;
  avg_sale: number;
  total_transactions: number;
  sales_trend: SalesTrend[];
  period: string;
}

const periods = [
  { label: '7 Days', value: 'last_7_days' },
  { label: '30 Days', value: 'last_30_days' },
  { label: 'This Month', value: 'this_month' },
  { label: 'Last Month', value: 'last_month' },
];

export const SalesAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');

  const { data, isLoading, isError, error } = useGetSalesAnalyticsQuery(selectedPeriod);

  const analytics = data?.data as SalesAnalyticsData | undefined;
  console.log("analytics", data)

  // Memoized chart data
  const chartData = useMemo(() => {
    if (!analytics?.sales_trend) return null;

    return {
      labels: analytics.sales_trend.map((item) => item.date),
      datasets: [
        {
          label: 'Daily Sales (UGX)',
          data: analytics.sales_trend.map((item) => item.amount),
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.08)',
          borderWidth: 3,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 6,
        },
      ],
    };
  }, [analytics]);

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index' as const,
        intersect: false,
        callbacks: {
          label: (context: any) => ` UGX ${context.parsed.y.toLocaleString()}`,
        },
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: number) => `UGX ${(value / 1000).toFixed(1)}K`,
        },
      },
    },
  };

  const handlePeriodChange = (period: string) => {
    setSelectedPeriod(period);
  };

  // Loading State
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
          <Skeleton className='h-80 w-full' />
        </CardContent>
      </Card>
    );
  }

  // Error State
  if (isError) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <DollarSign className='h-6 w-6' />
            Sales Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Alert variant='destructive'>
            <AlertDescription>
              Failed to load sales analytics. {error?.data?.message && `(${error.data.message})`}
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    );
  }

  // Empty State
  if (!analytics || analytics?.sales_trend?.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <DollarSign className='h-6 w-6' />
            Sales Analytics
          </CardTitle>
        </CardHeader>
        <CardContent className='py-12 text-center'>
          <p className='text-muted-foreground'>No sales data available for this period.</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
          <div>
            <CardTitle className='flex items-center gap-2'>
              <DollarSign className='h-6 w-6' />
              Sales Analytics
            </CardTitle>
            <CardDescription className='capitalize'>{analytics.period.replace(/_/g, ' ')}</CardDescription>
          </div>

          {/* Period Selector */}
          <div className='flex gap-1 bg-muted p-1 rounded-lg'>
            {periods.map((p) => (
              <Button
                key={p.value}
                variant={selectedPeriod === p.value ? 'default' : 'ghost'}
                size='sm'
                onClick={() => handlePeriodChange(p.value)}
                className='text-xs font-medium'
              >
                {p.label}
              </Button>
            ))}
          </div>
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        {/* Summary Stats */}
        <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
          <div className='rounded-xl bg-muted p-5'>
            <p className='text-sm text-muted-foreground'>Total Sales</p>
            <p className='text-3xl font-semibold mt-2'>UGX {Number(analytics.total_sales).toLocaleString()}</p>
          </div>

          <div className='rounded-xl bg-muted p-5'>
            <p className='text-sm text-muted-foreground'>Average Sale</p>
            <p className='text-3xl font-semibold mt-2'>UGX {Number(analytics.avg_sale).toLocaleString()}</p>
          </div>

          <div className='rounded-xl bg-muted p-5'>
            <p className='text-sm text-muted-foreground'>Transactions</p>
            <p className='text-3xl font-semibold mt-2'>{analytics.total_transactions}</p>
          </div>
        </div>

        {/* Chart */}
        <div className='h-80 w-full pt-2'>{chartData && <Line data={chartData} options={chartOptions} />}</div>
      </CardContent>
    </Card>
  );
};
