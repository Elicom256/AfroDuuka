import { useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Doughnut } from 'react-chartjs-2';
import { Package, AlertTriangle } from 'lucide-react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

import { useBranchProductAnalyticsQuery } from '@/app/store/features/branch/products/branchProductsQuery';

import { LoadingState } from '@/utils/LoadingState';
import { Error } from './Error';
import { InventorySummary } from './InventorySummary';
import { Footer } from 'react-day-picker';

ChartJS.register(ArcElement, Tooltip, Legend);

export const InventoryAnalytics = () => {
  const { data, isLoading, isError, error } = useBranchProductAnalyticsQuery();

  const analytics = data?.data;

  // All hooks must be at the top level
  const statusChartData = useMemo(() => {
    const breakdown = analytics?.statusBreakdown || [];
    const labels = breakdown.map((item: any) => item.status);
    const values = breakdown.map((item: any) => item.totalByStatus || item.totalbystatus);

    return {
      labels: labels.length ? labels : ['No Data'],
      datasets: [
        {
          data: values.length ? values : [1],
          backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#3b82f6'],
          borderWidth: 2,
        },
      ],
    };
  }, [analytics]);

  // Early returns AFTER all hooks
  if (isLoading) {
    return <LoadingState />;
  }

  if (isError) {
    return <Error error={error} />;
  }

  if (!analytics) {
    return (
      <Card>
        <CardContent className='py-12 text-center'>
          <p className='text-muted-foreground'>No inventory data available</p>
        </CardContent>
      </Card>
    );
  }

  const {
    totalInventoryValue = 0,
    totalPotentialRevenue = 0,
    totalExpectedProfit = 0,
    lowStock = 0,
    outOfStock = 0,
  } = analytics;

  const summaryStats = [
    {
      title: 'TIV',
      description: 'Total Inventory Value',
      value: `UGX ${Number(totalInventoryValue).toLocaleString()}`,
    },
    {
      title: 'PR',
      description: 'Potential Revenue',
      value: `UGX ${Number(totalPotentialRevenue).toLocaleString()}`,
      valueClassName: 'text-emerald-600',
    },
    {
      title: 'EP',
      description: 'Expected Profit',
      value: `UGX ${Number(totalExpectedProfit).toLocaleString()}`,
      valueClassName: 'text-emerald-600',
    },
    {
      title: 'LS',
      description: 'Low Stock',
      value: lowStock,
      icon: AlertTriangle,
      valueClassName: 'text-orange-600',
      className: 'border border-orange-200',
    },
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Package className='h-6 w-6' />
          Inventory Analytics
        </CardTitle>
        <CardDescription>Stock health and value overview</CardDescription>
      </CardHeader>

      <CardContent className='space-y-6'>
        <InventorySummary stats={summaryStats} />

        {/* Status Breakdown Chart */}
        <div>
          <h3 className='text-lg font-medium mb-3'>Stock Status Breakdown</h3>
          <div className='h-80 w-full flex items-center justify-center'>
            <Doughnut
              data={statusChartData}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
              }}
            />
          </div>
        </div>

        {/* Additional Insights */}
        <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
          <div className='rounded-xl border p-4 flex flex-col items-center justify-center'>
            <h4 className='font-medium mb-2'>Out of Stock</h4>
            <p className='text-3xl font-bold text-red-600'>{outOfStock}</p>
          </div>

          <div className='rounded-xl border p-4 flex flex-col items-center justify-center'>
            <h4 className='font-medium mb-2'>Slow Moving Items</h4>
            <p className='text-3xl font-bold'>{analytics.slowMoving?.length || 0}</p>
          </div>
        </div>
      </CardContent>
      <Footer className='border-t pt-4 px-1'>
        <div className='flex flex-wrap items-center justify-center gap-2 text-sm text-muted-foreground'>
          {summaryStats.map((stat) => (
            <div key={stat.title} className='flex items-center gap-1'>
              <span className='font-semibold text-foreground'>{stat.title}:</span>
              <span className='text-sm italic'>{stat.description}</span>
            </div>
          ))}
        </div>
      </Footer>
    </Card>
  );
};
