import { Link, useParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePurchaseReturnQuery } from '@/app/store/features/branch/purchase-returns/purchaseReturnsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeftCircle } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';

export const PurchaseReturn = () => {
  const { currency } = useCurrency();
  const { id } = useParams<{ id: string }>();
  if (!id) return null;
  const { data: prData, isLoading } = usePurchaseReturnQuery(id, { skip: !id });
  if (isLoading) return <PageLoadingState />;

  const pr = prData?.purchase_return || prData;
  if (!pr) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Purchase return not found</p>
      </div>
    );
  }

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Link to='../purchase-returns' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Purchase Returns</span>
        </Link>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            Purchase Return Details
            <Badge variant='secondary'>ID: {pr.id}</Badge>
          </CardTitle>
          <CardDescription className='flex items-center italic'>
            <span>Date: {format(new Date(pr.created_at), 'PPP')}</span>
          </CardDescription>
        </CardHeader>
        <CardContent className='space-y-6'>
          <div className='grid gap-4 md:grid-cols-2'>
            <div>
              <h3 className='font-semibold mb-2 text-lg'>Return Info</h3>
              <div className='space-y-2 text-sm'>
                <p><span className='font-medium'>Supplier:</span> {pr.supplier?.name || pr.supplier_id || 'N/A'}</p>
                <p><span className='font-medium'>Reason:</span> {pr.reason || 'N/A'}</p>
                <p><span className='font-medium'>Notes:</span> {pr.notes || 'No notes'}</p>
                <p><span className='font-medium'>Refund Amount:</span> {currency} {Number(pr.refund_amount).toLocaleString()}</p>
                <p><span className='font-medium'>Reduce Inventory:</span> {pr.restock ? 'No' : 'Yes'}</p>
                <p><span className='font-medium'>Status:</span> {pr.status}</p>
              </div>
            </div>
          </div>

          <div>
            <h3 className='font-semibold mb-4'>Returned Items</h3>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>No</TableHead>
                  <TableHead>Product</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Cost Price ({currency})</TableHead>
                  <TableHead>Subtotal ({currency})</TableHead>
                  <TableHead>Condition</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {pr.purchase_return_items?.map((item: any, i: number) => (
                  <TableRow key={item.id}>
                    <TableCell>{i + 1}</TableCell>
                    <TableCell>{item.purchase_item?.product?.name || 'N/A'}</TableCell>
                    <TableCell>{item.quantity}</TableCell>
                    <TableCell>{Number(item.cost_price || item.purchase_item?.cost_price).toLocaleString()}</TableCell>
                    <TableCell>{Number(item.subtotal).toLocaleString()}</TableCell>
                    <TableCell>{item.condition || '-'}</TableCell>
                  </TableRow>
                )) || (
                  <TableRow>
                    <TableCell colSpan={6} className='text-center text-muted-foreground'>No items found</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
