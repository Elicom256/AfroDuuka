import { useState, useMemo, useRef } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Line } from 'react-chartjs-2';
import { ShoppingCart } from 'lucide-react';
import { periods } from '../periodHelper';
import { SummaryCardContent } from './SummaryCardContent';
import { LoadingState } from '@/utils/LoadingState';
import { Error } from './Error';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';

export const PurchasesAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');
  const { data, isLoading, isError, error } = usePurchaseAnalyticsQuery(selectedPeriod);
  const chartRef = useRef<any>(null);

  const analytics = data?.data;
  // console.log('analytics==>', analytics);
  const chartData = useMemo(() => {
    if (!analytics?.purchase_trend) return null;

    return {
      labels: analytics.purchase_trend.map((item: any) => item.date),
      datasets: [
        {
          label: 'Daily Purchases (UGX)',
          data: analytics.purchase_trend.map((item: any) => item.amount),
          borderColor: '#f97316',
          backgroundColor: 'rgba(249, 115, 22, 0.08)',
          borderWidth: 3,
          tension: 0.4,
          pointRadius: 3,
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
  // useEffect(() => {
  //   return () => {
  //     if (chartRef.current) chartRef.current.destroy();
  //   };
  // }, []);

  const handlePeriodChange = (period: string) => setSelectedPeriod(period);

  if (isLoading) {
    return <LoadingState />;
  }

  if (isError) {
    return <Error error={error} />;
  }

  if (!analytics?.purchase_trend?.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <ShoppingCart className='h-6 w-6' /> Purchases Analytics
          </CardTitle>
        </CardHeader>
        <CardContent className='py-12 text-center'>
          <p className='text-muted-foreground'>No purchase data available for this period.</p>
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
              <ShoppingCart className='h-6 w-6' /> Purchases Analytics
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
          <Line ref={chartRef} data={chartData!} options={chartOptions} key={`purchases-${selectedPeriod}`} />
        </div>
      </CardContent>
    </Card>
  );
};
