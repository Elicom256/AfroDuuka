import { useState, useMemo, useEffect, useRef } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Line } from 'react-chartjs-2';
import { DollarSign } from 'lucide-react';
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
import { periods } from '../periodHelper';
import { SummaryCardContent } from './SummaryCardContent';
import { LoadingState } from '@/utils/LoadingState';
import { Error } from './Error';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export const SalesAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');
  const { data, isLoading, isError, error } = useGetSalesAnalyticsQuery(selectedPeriod);
  const chartRef = useRef<any>(null);

  const analytics = data?.data;

  const chartData = useMemo(() => {
    if (!analytics?.sales_trend) return null;

    return {
      labels: analytics.sales_trend.map((item: any) => item.date),
      datasets: [
        {
          label: 'Daily Sales (UGX)',
          data: analytics.sales_trend.map((item: any) => item.amount),
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
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: string | number) => {
            const num = typeof value === 'string' ? parseFloat(value) : value;
            return `UGX ${(num / 1000000).toFixed(2)}M`;
          },
        },
      },
    },
  } as const;

  // Cleanup
  useEffect(() => {
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
      }
    };
  }, []);

  const handlePeriodChange = (period: string) => setSelectedPeriod(period);

  if (isLoading) {
    return <LoadingState />;
  }

  if (isError) {
    return <Error error={error} />;
  }

  if (!analytics?.sales_trend?.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <DollarSign className='h-6 w-6' /> Sales Analytics
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
              <DollarSign className='h-6 w-6' /> Sales Analytics
            </CardTitle>
            <CardDescription className='capitalize'>{analytics.period.replace(/_/g, ' ')}</CardDescription>
          </div>

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
        <SummaryCardContent analytics={analytics} />

        <div className='h-80 w-full pt-2'>
          <Line ref={chartRef} data={chartData!} options={chartOptions} key={`sales-${selectedPeriod}`} />
        </div>
      </CardContent>
    </Card>
  );
};
