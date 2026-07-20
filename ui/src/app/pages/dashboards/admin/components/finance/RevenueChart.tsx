import { useState } from 'react';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCurrency } from '@/app/hooks/useCurrency';
import { format } from 'date-fns';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

type RevenueData = {
  date: string;
  revenue: number;
};

type RevenueChartProps = {
  data: RevenueData[];
  currency?: string;
};

export const RevenueChart = ({ data, currency: propCurrency }: RevenueChartProps) => {
  const { currency: hookCurrency } = useCurrency();
  const currency = propCurrency || hookCurrency || 'USD';
  const [period, setPeriod] = useState<'daily' | 'weekly' | 'monthly'>('daily');

  const chartData = {
    labels: data.map((d) => {
      try {
        return format(new Date(d.date), period === 'daily' ? 'dd MMM' : period === 'weekly' ? "'W'w" : 'MMM yyyy');
      } catch {
        return d.date;
      }
    }),
    datasets: [
      {
        label: `Revenue (${currency})`,
        data: data.map((d) => Number(d.revenue)),
        backgroundColor: '#10b981',
        borderRadius: 6,
        barThickness: 40,
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
            return `${currency} ${num.toLocaleString()}`;
          },
        },
      },
    },
  } as const;

  const periods: ('daily' | 'weekly' | 'monthly')[] = ['daily', 'weekly', 'monthly'];

  return (
    <Card>
      <CardHeader>
        <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
          <CardTitle>Revenue Report</CardTitle>
          <div className='flex gap-1'>
            {periods.map((p) => (
              <button
                key={p}
                onClick={() => setPeriod(p)}
                className={`px-3 py-1 text-sm rounded-md transition-colors ${
                  period === p ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'
                }`}
              >
                {p.charAt(0).toUpperCase() + p.slice(1)}
              </button>
            ))}
          </div>
        </div>
      </CardHeader>
      <CardContent className='space-y-6'>
        <div className='h-72 w-full'>
          <Bar data={chartData} options={chartOptions} />
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Date</TableHead>
              <TableHead className='text-right'>Revenue</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={2} className='py-10 text-center text-muted-foreground'>
                  No revenue data available.
                </TableCell>
              </TableRow>
            ) : (
              data.map((d, i) => (
                <TableRow key={i}>
                  <TableCell>{d.date}</TableCell>
                  <TableCell className='text-right font-medium'>
                    {currency} {Number(d.revenue).toLocaleString()}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};
