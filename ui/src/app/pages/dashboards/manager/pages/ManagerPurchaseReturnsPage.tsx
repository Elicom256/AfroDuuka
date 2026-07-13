import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  usePurchaseReturnsQuery,
  useAddPurchaseReturnMutation,
} from '@/app/store/features/branch/purchase-returns/purchaseReturnsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddPurchaseReturn } from '../components/purchase-returns/AddPurchaseReturn';
import { PurchaseReturnsTable } from '../components/purchase-returns/PurchaseReturnsTable';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ManagerPurchaseReturnsPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = usePurchaseReturnsQuery();
  const [addPurchaseReturn] = useAddPurchaseReturnMutation();

  if (isLoading) return <PageLoadingState />;

  const purchaseReturns = data?.purchase_returns ?? data ?? [];

  const totalReturns = purchaseReturns.length;
  const totalRefunded = purchaseReturns.reduce((sum: number, pr: any) => sum + Number(pr.refund_amount || 0), 0);

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-2'>
        <CardHeader>
          <CardTitle>Purchase Returns</CardTitle>
          <CardDescription>Manage returns to suppliers across any purchase.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <div className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total Returns</p>
              <p className='text-lg font-semibold'>{totalReturns}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-2 text-center'>
              <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total Refunded</p>
              <p className='text-lg font-semibold'>{currency} {totalRefunded.toLocaleString()}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-3'>
        <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <CardTitle>Purchase Return entries</CardTitle>
          </div>
          <AddPurchaseReturn addPurchaseReturn={addPurchaseReturn} />
        </CardHeader>
        <CardContent>
          <PurchaseReturnsTable purchaseReturns={purchaseReturns} />
        </CardContent>
      </Card>
    </div>
  );
};
