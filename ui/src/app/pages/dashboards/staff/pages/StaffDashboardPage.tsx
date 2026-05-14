import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign, Package } from 'lucide-react';

export const StaffDashboardPage = () => {
  const { data } = useLoggedinUserQuery();
  console.log('current user==>', data);
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Staff Dashboard</h1>
        <p className='text-muted-foreground'>
          Daily operations overview at {`${data?.data.business.name}, ${data?.data.business_branch.name}`}{' '}
        </p>
      </div>

      <div className='grid gap-6 md:grid-cols-2'>
        <Card>
          <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>Today's Sales</CardTitle>
            <DollarSign className='h-4 w-4 text-muted-foreground' />
          </CardHeader>
          <CardContent>
            <div className='text-2xl font-bold'>$1,234.56</div>
            <p className='text-xs text-muted-foreground'>+10% from yesterday</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
            <CardTitle className='text-sm font-medium'>Low Stock Items</CardTitle>
            <Package className='h-4 w-4 text-muted-foreground' />
          </CardHeader>
          <CardContent>
            <div className='text-2xl font-bold'>5</div>
            <p className='text-xs text-muted-foreground'>Items need restocking</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
