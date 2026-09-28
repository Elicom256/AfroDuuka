import { useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Bar, Doughnut } from 'react-chartjs-2';
import { BarChart3, PieChart } from 'lucide-react';

type ExpenseChartsProps = {
  monthlySummary: any[];
  totalsByCategory: any[];
};

export const ExpenseCharts = ({ monthlySummary, totalsByCategory }: ExpenseChartsProps) => {
  const monthlyChartData = useMemo(() => ({
    labels: monthlySummary.map((m: any) => m.month),
    datasets: [{
      label: 'Monthly Expenses',
      data: monthlySummary.map((m: any) => Number(m.total)),
      backgroundColor: '#ef4444',
      borderRadius: 6,
    }],
  }), [monthlySummary]);

  const categoryChartData = useMemo(() => ({
    labels: totalsByCategory.map((t: any) => t.category?.name ?? 'Unknown'),
    datasets: [{
      data: totalsByCategory.map((t: any) => Number(t.total)),
      backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'],
    }],
  }), [totalsByCategory]);

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
  };

  return (
    <div className='grid gap-6 md:grid-cols-2'>
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <BarChart3 className='h-5 w-5' />
            Monthly Summary
          </CardTitle>
          <CardDescription>Expense trend over the year</CardDescription>
        </CardHeader>
        <CardContent>
          <div className='h-72'>
            <Bar data={monthlyChartData} options={chartOptions} />
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <PieChart className='h-5 w-5' />
            By Category
          </CardTitle>
          <CardDescription>Expense distribution across categories</CardDescription>
        </CardHeader>
        <CardContent>
          <div className='h-72'>
            <Doughnut data={categoryChartData} options={{ responsive: true, maintainAspectRatio: false }} />
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
