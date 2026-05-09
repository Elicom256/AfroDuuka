import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  usePurchasesQuery,
  useAddPurchaseMutation,
  useUpdatePurchaseMutation,
  useDeletePurchaseMutation,
} from '@/app/store/features/business/purchases/purchasesQuery';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddPurchase } from '../components/purchases/AddPurchase';
import { PurchasesTable } from '../components/purchases/PurchasesTable';
import { EditPurchase } from '../components/purchases/EditPurchase';

export const AdminPurchasesPage = () => {
  const { data, isLoading } = usePurchasesQuery();
  const { data: productData } = useProductsQuery();
  const [addPurchase] = useAddPurchaseMutation();
  const [updatePurchase] = useUpdatePurchaseMutation();
  const [deletePurchase] = useDeletePurchaseMutation();
  const [editPurchase, setEditPurchase] = useState<any>(null);

  if (isLoading) return <PageLoadingState />;

  const purchases = data?.purchases ?? data ?? [];
  const products = productData?.products ?? [];
  const totalCost = purchases.reduce((sum: number, purchase: any) => {
    const unitCost = Number(purchase.unit_cost ?? purchase.price ?? 0);
    const quantity = Number(purchase.quantity ?? 0);
    return sum + unitCost * quantity;
  }, 0);
  const totalEntries = purchases.length;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Purchases</CardTitle>
          <CardDescription>Track incoming stock and supplier orders.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Entries</p>
              <p className='mt-4 text-xl font-semibold'>{totalEntries}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total spend</p>
              <p className='mt-4 text-xl font-semibold'>UGX {totalCost.toLocaleString()}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Products ordered</p>
              <p className='mt-4 text-xl font-semibold'>
                {purchases.reduce((sum: number, purchase: any) => sum + Number(purchase.quantity ?? 0), 0)}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <CardTitle>Purchases entries</CardTitle>
            <CardDescription>Manage purchase orders linked directly to products.</CardDescription>
          </div>
          <AddPurchase addPurchase={addPurchase} products={products} />
        </CardHeader>
        <CardContent>
          <PurchasesTable
            purchases={purchases}
            products={products}
            onEdit={(purchase) => setEditPurchase(purchase)}
            onDelete={async (id) => {
              await deletePurchase(id).unwrap();
            }}
          />
        </CardContent>
      </Card>

      {editPurchase && (
        <EditPurchase
          open={Boolean(editPurchase)}
          onOpenChange={(open) => {
            if (!open) setEditPurchase(null);
          }}
          purchase={editPurchase}
          products={products}
          updatePurchase={updatePurchase}
        />
      )}
    </div>
  );
};
