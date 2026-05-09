import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const AdminOrdersPage = () => {
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Orders</CardTitle>
        <CardDescription>Placeholder page for order monitoring.</CardDescription>
      </CardHeader>
      <CardContent>
        <div className='rounded-3xl border border-dashed border-border/50 bg-muted p-6 text-center'>
          <p className='text-lg font-semibold'>Coming soon</p>
          <p className='mt-2 text-sm text-muted-foreground'>
            Order management and reporting will appear here once expanded.
          </p>
        </div>
      </CardContent>
    </Card>
  );
};
