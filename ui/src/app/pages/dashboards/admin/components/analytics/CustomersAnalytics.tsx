import { useBranchCustomersQuery } from '@/app/store/features/branch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { Users } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const CustomersAnalytics = () => {
  const { data, isLoading, isError } = useBranchCustomersQuery();
  const customers = data?.data || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Users className='h-6 w-6' />
            Customers Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Skeleton className='h-64 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError || !customers.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Users className='h-6 w-6' />
            Customers Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-sm text-muted-foreground'>No customers data available</p>
        </CardContent>
      </Card>
    );
  }

  const totalCustomers = customers.length;
  const activeCustomers = customers.filter((c: any) => c.is_active !== false).length;
  const newCustomers = customers.filter((c: any) => {
    const created = new Date(c.created_at || c.date);
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    return created > thirtyDaysAgo;
  }).length;

  // Group customers by registration date
  const customersByMonth: Record<string, number> = {};
  customers.forEach((customer: any) => {
    const date = new Date(customer.created_at || customer.date).toLocaleDateString('en-US', {
      month: 'short',
      year: '2-digit',
    });
    customersByMonth[date] = (customersByMonth[date] || 0) + 1;
  });

  const chartData = {
    labels: Object.keys(customersByMonth).slice(-6),
    datasets: [
      {
        label: 'New Customers',
        data: Object.values(customersByMonth).slice(-6),
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
      },
    ],
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Users className='h-6 w-6' />
          Customers Analytics
        </CardTitle>
        <CardDescription>Customer base overview</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid gap-2 sm:grid-cols-3'>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Total Customers</p>
            <p className='text-xl font-semibold'>{totalCustomers}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Active</p>
            <p className='text-xl font-semibold text-green-600'>{activeCustomers}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>New (30d)</p>
            <p className='text-xl font-semibold text-blue-600'>{newCustomers}</p>
          </div>
        </div>
        <div className='h-64 w-full'>
          <Bar data={chartData} options={{ responsive: true, maintainAspectRatio: false }} />
        </div>
      </CardContent>
    </Card>
  );
};
