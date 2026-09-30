import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Filler,
} from 'chart.js';
import { TrendingUp } from 'lucide-react';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { landingPeriodLabel, type LandingPeriod } from '../landingPeriods';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

export const RevenueChart = ({ period }: { period: LandingPeriod }) => {
  const { currency } = useCurrency();
  const { data, isLoading } = useGetSalesAnalyticsQuery(period);

  const trend = data?.data?.sales_trend ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent>
          <Skeleton className='h-72 w-full' />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <TrendingUp className='h-4 w-4 text-primary' />
          Revenue trend
        </CardTitle>
        <CardDescription>Daily revenue · {landingPeriodLabel(period)}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className='h-72'>
          <Line
            data={{
              labels: trend.map((p: any) => p.date),
              datasets: [
                {
                  label: 'Revenue',
                  data: trend.map((p: any) => Number(p.amount) || 0),
                  borderColor: '#6366f1',
                  backgroundColor: 'rgba(99, 102, 241, 0.12)',
                  fill: true,
                  tension: 0.4,
                  pointRadius: 0,
                  pointHoverRadius: 4,
                },
              ],
            }}
            options={{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: (context: any) => ` ${currency} ${Number(context.parsed.y).toLocaleString()}`,
                  },
                },
              },
              scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                y: {
                  border: { display: false },
                  ticks: {
                    callback: (value: any) => `${Number(value) / 1000}k`,
                  },
                },
              },
            }}
          />
        </div>
      </CardContent>
    </Card>
  );
};
