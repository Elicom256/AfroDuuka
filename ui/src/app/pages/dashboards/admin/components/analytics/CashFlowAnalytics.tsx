import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Bar } from 'react-chartjs-2';
import { TrendingUp, ArrowUp, ArrowDown } from 'lucide-react';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { useCashFlowAnalyticsQuery } from '@/app/store/features/business/branches/branchesQuery';
import { PeriodFilterBar } from '../PeriodFilterBar';
import { type ReportFilter } from '@/types';
import { Error } from './Error';
import { LoadingState } from '@/utils/LoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const CashFlowAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState<ReportFilter>('last_7_days');
  const { data, isLoading, isError, error } = useCashFlowAnalyticsQuery(selectedPeriod);
  const { currency } = useCurrency();

  const analytics = data?.data;

  const handlePeriodChange = (period: ReportFilter) => setSelectedPeriod(period);

  if (isLoading) {
    return <LoadingState />;
  }

  if (isError || !analytics) {
    return <Error error={error} />;
  }

  const { total_revenue, total_expenses, net_cash_flow } = analytics;
  const isPositive = net_cash_flow >= 0;

  const chartData = {
    labels: ['Revenue', 'Expenses'],
    datasets: [
      {
        label: `Amount (${currency})`,
        data: [Number(total_revenue), Number(total_expenses)],
        backgroundColor: ['#10b981', '#ef4444'],
        borderRadius: 8,
        barThickness: 60,
      },
    ],
  };

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
            return `${currency} ${(num / 1000000).toFixed(1)}M`;
          },
        },
      },
    },
  } as const; // ← This helps TypeScript

  return (
    <Card>
      <CardHeader>
        <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
          <div>
            <CardTitle className='flex items-center gap-2'>
              <TrendingUp className='h-6 w-6' />
              Cash Flow Analytics
            </CardTitle>
            <CardDescription className='capitalize'>{selectedPeriod.replace(/_/g, ' ')}</CardDescription>
          </div>

          <PeriodFilterBar selected={selectedPeriod} onChange={handlePeriodChange} />
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        {/* Net Cash Flow Highlight */}
        <div
          className={`rounded-2xl p-2 text-center ${isPositive ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'}`}
        >
          <p className='text-sm text-muted-foreground'>Net Cash Flow</p>
          <p
            className={`text-lg font-semibold mt-2 flex items-center justify-center gap-2 ${isPositive ? 'text-emerald-600' : 'text-red-600'}`}
          >
            {isPositive ? <ArrowUp className='h-8 w-8' /> : <ArrowDown className='h-8 w-8' />}
            {currency} {Number(net_cash_flow).toLocaleString()}
          </p>
        </div>

        {/* Revenue vs Expenses Bar Chart */}
        <div>
          <h3 className='text-lg font-medium mb-3'>Revenue vs Expenses</h3>
          <div className='h-70 w-full'>
            <Bar data={chartData} options={chartOptions} />
          </div>
        </div>
      </CardContent>
    </Card>
  );
};
