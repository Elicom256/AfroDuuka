import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  usePurchasesQuery,
  useAddPurchaseMutation,
  useUpdatePurchaseMutation,
} from '@/app/store/features/business/purchases/purchasesQuery';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddPurchase } from '../components/purchases/AddPurchase';
import { PurchasesTable } from '../components/purchases/PurchasesTable';
import { EditPurchase } from '../components/purchases/EditPurchase';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';

export const ManagerPurchasesPage = () => {
  const { data, isLoading } = usePurchasesQuery();
  const { data: productData } = useProductsQuery();
  const { data: sup } = useSuppliersQuery();
  const [addPurchase, { isLoading: loadNewPurchase }] = useAddPurchaseMutation();
  const [updatePurchase] = useUpdatePurchaseMutation();
  const [editPurchase, setEditPurchase] = useState<any>(null);
  const suppliers = sup?.suppliers || [];
  console.log('purchases==>', data);
  if (isLoading || loadNewPurchase) return <PageLoadingState />;

  const purchases = data?.purchases ?? data ?? [];
  const products = productData?.products ?? [];

  // Calculate total amount from all purchases
  const totalPurchaseAmount = purchases?.reduce((sum: number, purchase: any) => {
    return sum + Number(purchase.total_amount ?? 0);
  }, 0);

  // Calculate total items purchased
  const totalItemsPurchased = purchases?.reduce((sum: number, purchase: any) => {
    const items = purchase.purchase_items || purchase.items || [];
    const itemCount = items.reduce((acc: number, item: any) => acc + Number(item.quantity ?? 0), 0);
    return sum + itemCount;
  }, 0);

  const totalOrders = purchases.length;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-2'>
        <CardHeader>
          <CardTitle>Purchases</CardTitle>
          <CardDescription>Track incoming stock and supplier orders.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-3'>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total Purchases</p>
              <p className='text-lg font-semibold'>{totalOrders}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total Spent</p>
              <p className='text-lg font-semibold'>UGX {totalPurchaseAmount.toLocaleString()}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Products Ordered</p>
              <p className='text-lg font-semibold'>{totalItemsPurchased}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <CardTitle>Purchase entries</CardTitle>
            <CardDescription>Manage purchase orders linked to suppliers.</CardDescription>
          </div>
          <AddPurchase addPurchase={addPurchase} products={products} suppliers={suppliers} />
        </CardHeader>
        <CardContent>
          <PurchasesTable purchases={purchases} products={products} />
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
