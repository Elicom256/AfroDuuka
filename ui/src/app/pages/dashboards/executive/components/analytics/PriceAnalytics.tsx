/*-----------------------------------------------------------------------------------
 * Component: PriceAnalytics
 * -------------------------------
 * Displays price change trends over time using Chart.js (Line chart).
 * Follows the same analytics pattern as SalesAnalytics / PurchasesAnalytics:
 *   - Period filter bar
 *   - Summary card with aggregate metrics
 *   - Line chart for daily price change counts
 *   - Most-changed products list
 *---------------------------------------------------------------------------------*/

import { useState, useMemo, useEffect, useRef } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Line } from 'react-chartjs-2';
import { TrendingUp, Hash } from 'lucide-react';
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
import { usePriceHistoryAnalyticsQuery } from '@/app/store/features/branch/priceHistory/priceHistoryQuery';
import { PeriodFilterBar } from '../PeriodFilterBar';
import { type ReportFilter } from '@/types';
import { Badge } from '@/components/ui/badge';
import { LoadingState } from '@/utils/LoadingState';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

// Register Chart.js components (both Line and Bar elements needed)
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

export const PriceAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState<ReportFilter>('last_7_days');
  const { data, isLoading, isFetching, isError, refetch } = usePriceHistoryAnalyticsQuery(selectedPeriod);
  const chartRef = useRef<any>(null);

  const analytics = data?.data;

  /*
   * Build chart data for the price change count trend
   */
  const chartData = useMemo(() => {
    if (!analytics?.trend?.length) return null;

    return {
      labels: analytics.trend.map((item: any) => item.date),
      datasets: [
        {
          label: 'Price Changes',
          data: analytics.trend.map((item: any) => item.count),
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.08)',
          borderWidth: 3,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: true,
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
          stepSize: 1,
        },
      },
    },
  } as const;

  // Cleanup chart instance on unmount
  useEffect(() => {
    // Captured at effect time: reading chartRef.current from the cleanup can hit a
    // different (or null) node than the one this effect attached to, which leaks the
    // chart instance instead of destroying it.
    const chart = chartRef.current;
    return () => {
      if (chart) {
        chart.destroy();
      }
    };
  }, []);

  const handlePeriodChange = (period: ReportFilter) => setSelectedPeriod(period);

  if (isLoading) return <LoadingState />;

  if (isError) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <TrendingUp className='h-6 w-6' /> Price Change Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <QueryErrorState
            title='Unable to load price analytics'
            description='Price history data could not be retrieved.'
            onRetry={refetch}
            retrying={isFetching}
          />
        </CardContent>
      </Card>
    );
  }

  if (!analytics) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <TrendingUp className='h-6 w-6' /> Price Change Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <QueryEmptyState title='No price history data' description='There are no price changes for this period.' />
        </CardContent>
      </Card>
    );
  }

  return (
    <div className='space-y-6'>
      {/* Trend chart card */}
      <Card>
        <CardHeader>
          <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
            <div>
              <CardTitle className='flex items-center gap-2'>
                <TrendingUp className='h-6 w-6' /> Price Change Analytics
              </CardTitle>
              <CardDescription className='capitalize'>{analytics.period?.replace(/_/g, ' ')}</CardDescription>
            </div>
            <PeriodFilterBar selected={selectedPeriod} onChange={handlePeriodChange} />
          </div>
        </CardHeader>

        <CardContent className='space-y-6'>
          {/* Summary metrics */}
          <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
            <Card>
              <CardContent className='pt-6'>
                <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                  <Hash className='h-4 w-4' />
                  Total Changes
                </div>
                <p className='text-2xl font-bold mt-1'>{analytics.total_changes ?? 0}</p>
              </CardContent>
            </Card>
          </div>

          {/* Chart */}
          {chartData && (
            <div className='h-80 w-full pt-2'>
              <Line ref={chartRef} data={chartData} options={chartOptions} key={`price-chart-${selectedPeriod}`} />
            </div>
          )}

          {(!chartData || analytics.trend?.length === 0) && (
            <div className='h-40 flex items-center justify-center text-muted-foreground'>
              No price changes recorded in this period.
            </div>
          )}
        </CardContent>
      </Card>

      {/* Most-changed products */}
      {analytics.most_changed?.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2 text-lg'>
              <Hash className='h-5 w-5' /> Most Changed Products
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className='space-y-3'>
              {analytics.most_changed.map((item: any, idx: number) => (
                <div key={item.product_id} className='flex items-center justify-between py-2 border-b last:border-b-0'>
                  <div className='flex items-center gap-3'>
                    <span className='text-sm font-medium text-muted-foreground w-6'>#{idx + 1}</span>
                    <span className='font-medium'>{item.product?.name ?? `Product #${item.product_id}`}</span>
                  </div>
                  <Badge variant='secondary'>{item.changes} changes</Badge>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
