import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  useOrdersQuery,
  useAddOrderMutation,
  useUpdateOrderMutation,
  useDeleteOrderMutation,
} from '@/app/store/features/business/sales/salesQuery';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddSale } from '../components/sales/AddSale';
import { SalesTable } from '../components/sales/SalesTable';
import { EditSale } from '../components/sales/EditSale';

export const AdminSalesPage = () => {
  const { data, isLoading } = useOrdersQuery();
  const { data: productData } = useProductsQuery();
  const [addOrder] = useAddOrderMutation();
  const [updateOrder] = useUpdateOrderMutation();
  const [deleteOrder] = useDeleteOrderMutation();
  const [editSale, setEditSale] = useState<any>(null);

  if (isLoading) return <PageLoadingState />;

  const sales = data?.orders ?? data ?? [];
  const products = productData?.products ?? [];
  const totalSalesAmount = sales.reduce((sum: number, sale: any) => {
    const unitPrice = Number(sale.unit_price ?? sale.price ?? 0);
    const quantity = Number(sale.quantity ?? 0);
    return sum + unitPrice * quantity;
  }, 0);
  const totalOrders = sales.length;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Sales</CardTitle>
          <CardDescription>Record and manage product sales.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Orders</p>
              <p className='mt-4 text-3xl font-semibold'>{totalOrders}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total revenue</p>
              <p className='mt-4 text-3xl font-semibold'>UGX {totalSalesAmount.toLocaleString()}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Products sold</p>
              <p className='mt-4 text-3xl font-semibold'>
                {sales.reduce((sum: number, sale: any) => sum + Number(sale.quantity ?? 0), 0)}
              </p>
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
          <AddSale addOrder={addOrder} products={products} />
        </CardHeader>
        <CardContent>
          <SalesTable
            sales={sales}
            products={products}
            onEdit={(sale) => setEditSale(sale)}
            onDelete={async (id) => {
              await deleteOrder(id).unwrap();
            }}
          />
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
          updateOrder={updateOrder}
        />
      )}
    </div>
  );
};
