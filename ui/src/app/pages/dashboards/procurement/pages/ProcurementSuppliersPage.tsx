import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Badge } from '@/components/ui/badge';

export const ProcurementSuppliersPage = () => {
  const { data, isLoading } = useSuppliersQuery();

  if (isLoading) return <PageLoadingState />;

  const suppliers = data?.suppliers ?? [];

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Suppliers</CardTitle>
          <CardDescription>Manage supplier relationships for procurement.</CardDescription>
        </CardHeader>
        <CardContent>
          {suppliers.length > 0 ? (
            <div className='space-y-3'>
              {suppliers.map((supplier: any) => (
                <div
                  key={supplier.id}
                  className='flex items-center justify-between rounded-2xl border border-border/70 bg-muted p-4'
                >
                  <div>
                    <p className='font-medium'>{supplier.company_name ?? supplier.name ?? 'Supplier'}</p>
                    <p className='text-xs text-muted-foreground'>
                      {supplier.email} | {supplier.phone}
                    </p>
                  </div>
                  <Badge className={supplier.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'}>
                    {supplier.status}
                  </Badge>
                </div>
              ))}
            </div>
          ) : (
            <p className='text-sm text-muted-foreground'>No suppliers found.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
