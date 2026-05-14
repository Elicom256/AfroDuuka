import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign, ShoppingCart, Package } from 'lucide-react';

export const ManagerDashboardPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Manager Dashboard</h1>
        <p className='text-muted-foreground'>Overview of your branch operations</p>
      </div>

      <div className='grid gap-6 md:grid-cols-2 lg:grid-cols-3'>
        <Card>
          <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>Total Sales</CardTitle>
            <DollarSign className='h-4 w-4 text-muted-foreground' />
          </CardHeader>
          <CardContent>
            <div className='text-2xl font-bold'>$45,231.89</div>
            <p className='text-xs text-muted-foreground'>+20.1% from last month</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>Purchases</CardTitle>
            <ShoppingCart className='h-4 w-4 text-muted-foreground' />
          </CardHeader>
          <CardContent>
            <div className='text-2xl font-bold'>$12,234.00</div>
            <p className='text-xs text-muted-foreground'>+15% from last month</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>Inventory Items</CardTitle>
            <Package className='h-4 w-4 text-muted-foreground' />
          </CardHeader>
          <CardContent>
            <div className='text-2xl font-bold'>1,234</div>
            <p className='text-xs text-muted-foreground'>+5% from last month</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
