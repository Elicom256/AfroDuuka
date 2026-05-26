import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAddSaleMutation, useSalesQuery, useUpdateSaleMutation } from '@/app/store/features/branch/sales/salesQuery';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddSale } from '../components/sales/AddSale';
import { SalesTable } from '../components/sales/SalesTable';
import { EditSale } from '../components/sales/EditSale';
import { useBranchProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useAllowedPaymentMethodsQuery } from '@/app/store/features/business/settings/payment';

export const AdminSalesPage = () => {
  const { data, isLoading } = useSalesQuery();
  const { data: productData } = useBranchProductsQuery();
  const { data: methods } = useAllowedPaymentMethodsQuery();
  const [addSale] = useAddSaleMutation();
  const [updateSale] = useUpdateSaleMutation();
  const [editSale, setEditSale] = useState<any>(null);
  const paymentMethods = methods?.methods;
  console.log('paymentMethods==>', paymentMethods);
  if (isLoading) return <PageLoadingState />;

  const sales = data?.sales ?? data ?? [];
  const products = productData?.products ?? [];
  const totalSalesAmount = sales.reduce((sum: number, sale: any) => {
    const all = sale.sale_items.reduce((acc: number, val: any) => acc + val.quantity, 0);
    return sum + all;
  }, 0);
  // console.log('sales==>', data);

  const totals = sales.reduce((acc: number, val: any) => Number(acc) + Number(val.total_amount as string), 0);
  const totalOrders = sales.length;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-2'>
        <CardHeader>
          <CardTitle>Sales</CardTitle>
          <CardDescription>Record and manage product sales.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total Sales</p>
              <p className='text-lg font-semibold'>{totalOrders}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total revenue</p>
              <p className='text-lg font-semibold'>UGX {totals.toLocaleString()}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Products sold</p>
              <p className='text-lg font-semibold'>{totalSalesAmount}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <CardTitle>Sales entries</CardTitle>
            <CardDescription>Manage sales records linked to products.</CardDescription>
          </div>
          <AddSale addSale={addSale} products={products} paymentMethods={paymentMethods} />
        </CardHeader>
        <CardContent>
          <SalesTable sales={sales} products={products} />
        </CardContent>
      </Card>

      {editSale && (
        <EditSale
          open={Boolean(editSale)}
          onOpenChange={(open) => {
            if (!open) setEditSale(null);
          }}
          sale={editSale}
          products={products}
          updateSale={updateSale}
        />
      )}
    </div>
  );
};
