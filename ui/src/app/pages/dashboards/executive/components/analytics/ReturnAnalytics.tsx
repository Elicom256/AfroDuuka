import { useState, useMemo, useEffect, useRef } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Bar } from 'react-chartjs-2';
import { RotateCcw, TrendingDown, Package, DollarSign } from 'lucide-react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { useGetReturnAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { PeriodFilterBar } from '../PeriodFilterBar';
import { type ReportFilter } from '@/types';
import { LoadingState } from '@/utils/LoadingState';
import { Error } from './Error';
import { useCurrency } from '@/app/hooks/useCurrency';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const ReturnAnalytics = () => {
  const { currency } = useCurrency();
  const [selectedPeriod, setSelectedPeriod] = useState<ReportFilter>('last_7_days');
  const { data, isLoading, isFetching, isError, refetch } = useGetReturnAnalyticsQuery(selectedPeriod);
  const chartRef = useRef<any>(null);

  const analytics = data?.data;

  const chartData = useMemo(() => {
    if (!analytics?.returns_trend) return null;

    return {
      labels: analytics.returns_trend.map((item: any) => item.date),
      datasets: [
        {
          label: `Returned Revenue (${currency})`,
          data: analytics.returns_trend.map((item: any) => item.amount),
          backgroundColor: '#f59e0b',
          borderRadius: 6,
          barThickness: 40,
        },
      ],
    };
  }, [analytics, currency]);

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (context: any) => ` ${currency} ${context.parsed.y.toLocaleString()}`,
        },
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: string | number) => {
            const num = typeof value === 'string' ? parseFloat(value) : value;
            return `${currency} ${(num / 1000000).toFixed(2)}M`;
          },
        },
      },
    },
  } as const;

  useEffect(() => {
    const chart = chartRef.current;
    return () => {
      if (chart) {
        chart.destroy();
      }
    };
  }, []);

  const handlePeriodChange = (period: ReportFilter) => setSelectedPeriod(period);

  if (isLoading) {
    return <LoadingState />;
  }

  if (isError) {
    return <Error title='Unable to load return analytics' onRetry={refetch} retrying={isFetching} />;
  }

  if (!analytics) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <RotateCcw className='h-6 w-6' /> Return Analytics
          </CardTitle>
        </CardHeader>
        <CardContent className='py-12 text-center'>
          <p className='text-muted-foreground'>No return data available for this period.</p>
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
              <RotateCcw className='h-6 w-6' /> Return Analytics
            </CardTitle>
            <CardDescription className='capitalize'>{analytics.period.replace(/_/g, ' ')}</CardDescription>
          </div>

          <PeriodFilterBar selected={selectedPeriod} onChange={handlePeriodChange} />
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
          <div className='rounded-lg border bg-card p-4'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <DollarSign className='h-4 w-4' />
              Gross Sales
            </div>
            <p className='mt-2 text-2xl font-semibold'>
              {currency} {Number(analytics.gross_sales).toLocaleString()}
            </p>
          </div>

          <div className='rounded-lg border bg-card p-4'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <TrendingDown className='h-4 w-4' />
              Sales Returns
            </div>
            <p className='mt-2 text-2xl font-semibold text-amber-600'>
              {currency} {Number(analytics.sales_returns).toLocaleString()}
            </p>
          </div>

          <div className='rounded-lg border bg-card p-4'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <DollarSign className='h-4 w-4' />
              Net Sales
            </div>
            <p className='mt-2 text-2xl font-semibold text-emerald-600'>
              {currency} {Number(analytics.net_sales).toLocaleString()}
            </p>
          </div>

          <div className='rounded-lg border bg-card p-4'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <Package className='h-4 w-4' />
              Return Rate
            </div>
            <p className='mt-2 text-2xl font-semibold'>
              {/* Rate is undefined when the period has no sales to divide by, so it
                  comes back null rather than a misleading 0%. */}
              {analytics.return_rate === null || analytics.return_rate === undefined
                ? '—'
                : `${analytics.return_rate}%`}
            </p>
          </div>
        </div>

        <div className='h-80 w-full pt-2'>
          <Bar ref={chartRef} data={chartData!} options={chartOptions} key={`returns-${selectedPeriod}`} />
        </div>
      </CardContent>
    </Card>
  );
};
