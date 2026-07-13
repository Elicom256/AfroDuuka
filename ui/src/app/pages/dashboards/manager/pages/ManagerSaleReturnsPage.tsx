import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  useSaleReturnsQuery,
  useAddSaleReturnMutation,
} from '@/app/store/features/branch/sale-returns/saleReturnsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddSaleReturn } from '../components/sale-returns/AddSaleReturn';
import { SaleReturnsTable } from '../components/sale-returns/SaleReturnsTable';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ManagerSaleReturnsPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useSaleReturnsQuery();
  const [addSaleReturn] = useAddSaleReturnMutation();

  if (isLoading) return <PageLoadingState />;

  const saleReturns = data?.sale_returns ?? data ?? [];

  const totalReturns = saleReturns.length;
  const totalRefunded = saleReturns.reduce((sum: number, sr: any) => sum + Number(sr.refund_amount || 0), 0);

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-2'>
        <CardHeader>
          <CardTitle>Sale Returns</CardTitle>
          <CardDescription>Manage customer returns and refunds across any sale.</CardDescription>
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
            <CardTitle>Sale Return entries</CardTitle>
          </div>
          <AddSaleReturn addSaleReturn={addSaleReturn} />
        </CardHeader>
        <CardContent>
          <SaleReturnsTable saleReturns={saleReturns} />
        </CardContent>
      </Card>
    </div>
  );
};
