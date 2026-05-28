import { useBranchSuppliersQuery } from '@/app/store/features/branch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { Truck } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const SuppliersAnalytics = () => {
  const { data, isLoading, isError } = useBranchSuppliersQuery();
  const suppliers = data?.data || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Truck className='h-6 w-6' />
            Suppliers Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Skeleton className='h-64 w-full' />
        </CardContent>
      </Card>
    );
  }

  if (isError || !suppliers.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Truck className='h-6 w-6' />
            Suppliers Analytics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-sm text-muted-foreground'>No suppliers data available</p>
        </CardContent>
      </Card>
    );
  }

  const totalSuppliers = suppliers.length;
  const activeSuppliers = suppliers.filter((s: any) => s.is_active !== false).length;
  const totalDebt = suppliers.reduce(
    (sum: number, supplier: any) => sum + (supplier.outstanding_balance || supplier.credit_balance || 0),
    0,
  );

  // Top suppliers by transaction count
  const topSuppliers = suppliers
    .sort(
      (a: any, b: any) =>
        (b.total_purchases || b.transaction_count || 0) - (a.total_purchases || a.transaction_count || 0),
    )
    .slice(0, 5);

  const chartData = {
    labels: topSuppliers.map((s: any) => s.supplier_name || s.name).slice(0, 5),
    datasets: [
      {
        label: 'Total Purchases',
        data: topSuppliers.map((s: any) => s.total_purchases || s.transaction_count || 0),
        backgroundColor: 'rgba(153, 102, 255, 0.6)',
        borderColor: 'rgba(153, 102, 255, 1)',
        borderWidth: 2,
      },
    ],
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Truck className='h-6 w-6' />
          Suppliers Analytics
        </CardTitle>
        <CardDescription>Supplier relationships overview</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid gap-2 sm:grid-cols-3'>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Total Suppliers</p>
            <p className='text-xl font-semibold'>{totalSuppliers}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Active</p>
            <p className='text-xl font-semibold text-green-600'>{activeSuppliers}</p>
          </div>
          <div className='rounded-lg bg-muted p-3'>
            <p className='text-xs text-muted-foreground'>Outstanding Balance</p>
            <p className='text-xl font-semibold'>
              KSH {totalDebt.toLocaleString('en-US', { maximumFractionDigits: 0 })}
            </p>
          </div>
        </div>
        <div className='h-64 w-full'>
          <Bar data={chartData} options={{ responsive: true, maintainAspectRatio: false }} />
        </div>
      </CardContent>
    </Card>
  );
};
