import { Doughnut } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCurrency } from '@/app/hooks/useCurrency';

ChartJS.register(ArcElement, Tooltip, Legend);

type ExpenseCategory = {
  category: string;
  amount: number;
};

type ExpenseBreakdownProps = {
  data: ExpenseCategory[];
};

const CATEGORY_COLORS: Record<string, string> = {
  rent: '#ef4444',
  utilities: '#f97316',
  salaries: '#eab308',
  supplies: '#22c55e',
  marketing: '#3b82f6',
  maintenance: '#8b5cf6',
  insurance: '#ec4899',
  taxes: '#14b8a6',
  transport: '#6366f1',
  other: '#6b7280',
};

const FALLBACK_COLORS = [
  '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6',
  '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1', '#6b7280',
];

const getColor = (category: string, index: number): string => {
  const key = category.toLowerCase().replace(/\s+/g, '');
  return CATEGORY_COLORS[key] || FALLBACK_COLORS[index % FALLBACK_COLORS.length];
};

export const ExpenseBreakdown = ({ data }: ExpenseBreakdownProps) => {
  const { currency } = useCurrency();
  const total = data.reduce((sum, d) => sum + Number(d.amount), 0);

  const chartData = {
    labels: data.map((d) => d.category),
    datasets: [
      {
        data: data.map((d) => Number(d.amount)),
        backgroundColor: data.map((d, i) => getColor(d.category, i)),
        borderWidth: 2,
        borderColor: '#ffffff',
      },
    ],
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: {
          padding: 16,
          usePointStyle: true,
        },
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            const value = context.parsed;
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
            return ` ${context.label}: ${currency} ${value.toLocaleString()} (${percentage}%)`;
          },
        },
      },
    },
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Expense Breakdown</CardTitle>
      </CardHeader>
      <CardContent className='space-y-6'>
        <div className='h-72 w-full flex items-center justify-center'>
          {data.length === 0 ? (
            <p className='text-muted-foreground'>No expense data available.</p>
          ) : (
            <Doughnut data={chartData} options={chartOptions} />
          )}
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Category</TableHead>
              <TableHead className='text-right'>Amount</TableHead>
              <TableHead className='text-right'>Percentage</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={3} className='py-10 text-center text-muted-foreground'>
                  No expense data available.
                </TableCell>
              </TableRow>
            ) : (
              data.map((d, i) => {
                const pct = total > 0 ? ((Number(d.amount) / total) * 100).toFixed(1) : '0.0';
                return (
                  <TableRow key={i}>
                    <TableCell className='flex items-center gap-2'>
                      <span
                        className='inline-block w-3 h-3 rounded-full'
                        style={{ backgroundColor: getColor(d.category, i) }}
                      />
                      {d.category}
                    </TableCell>
                    <TableCell className='text-right font-medium'>
                      {currency} {Number(d.amount).toLocaleString()}
                    </TableCell>
                    <TableCell className='text-right text-muted-foreground'>{pct}%</TableCell>
                  </TableRow>
                );
              })
            )}
            <TableRow>
              <TableCell className='font-semibold'>Total</TableCell>
              <TableCell className='text-right font-semibold'>
                {currency} {total.toLocaleString()}
              </TableCell>
              <TableCell className='text-right font-semibold'>100%</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};
