import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const AdminProductsPage = () => {
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Products</CardTitle>
        <CardDescription>Placeholder page for product management.</CardDescription>
      </CardHeader>
      <CardContent>
        <div className='rounded-3xl border border-dashed border-border/50 bg-muted p-6 text-center'>
          <p className='text-lg font-semibold'>Coming soon</p>
          <p className='mt-2 text-sm text-muted-foreground'>
            Product management will be added here when you expand the admin dashboard.
          </p>
        </div>
      </CardContent>
    </Card>
  );
};
