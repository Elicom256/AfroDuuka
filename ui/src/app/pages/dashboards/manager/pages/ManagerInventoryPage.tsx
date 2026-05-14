import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const ManagerInventoryPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Inventory</h1>
        <p className='text-muted-foreground'>Track inventory levels for your branch</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Inventory Overview</CardTitle>
          <CardDescription>Monitor stock levels and alerts</CardDescription>
        </CardHeader>
        <CardContent>
          <p>Inventory table and tracking components will be implemented here.</p>
        </CardContent>
      </Card>
    </div>
  );
};
