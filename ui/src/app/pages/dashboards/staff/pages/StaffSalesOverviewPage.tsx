import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const StaffSalesOverviewPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Sales Overview</h1>
        <p className='text-muted-foreground'>View sales flow and trends</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Sales Trends</CardTitle>
          <CardDescription>Daily and weekly sales data</CardDescription>
        </CardHeader>
        <CardContent>
          <p>Charts and sales flow visualization will be implemented here.</p>
        </CardContent>
      </Card>
    </div>
  );
};
