import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const StaffInventoryPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Inventory</h1>
        <p className='text-muted-foreground'>Check stock levels</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Inventory Status</CardTitle>
          <CardDescription>Current stock information</CardDescription>
        </CardHeader>
        <CardContent>
          <p>Inventory list and alerts will be implemented here.</p>
        </CardContent>
      </Card>
    </div>
  );
};
