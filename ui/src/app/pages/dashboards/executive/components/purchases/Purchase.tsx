import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePurchaseQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeftCircle, PackageCheck } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Button } from '@/components/ui/button';
import { QueryErrorState } from '@/app/components/QueryErrorState';
import { ReceivePurchase } from './ReceivePurchase';

export const Purchase = () => {
  const { currency } = useCurrency();
  const { id } = useParams<{ id: string }>();
  const [receiveOpen, setReceiveOpen] = useState(false);
  const {
    data: purchaseData,
    isLoading: purchaseLoading,
    isFetching,
    error,
    refetch,
  } = usePurchaseQuery(Number(id), { skip: !id });
  if (purchaseLoading) return <PageLoadingState />;

  if (error) {
    return (
      <div className='space-y-4'>
        <QueryErrorState
          title='Unable to load purchase'
          description='Purchase details could not be retrieved from the server.'
          onRetry={refetch}
          retrying={isFetching}
        />
        <Button variant='ghost' asChild>
          <Link to='../purchases'>Back to purchases</Link>
        </Button>
      </div>
    );
  }

  const purchase = purchaseData?.purchase || purchaseData;

  if (!purchase) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Purchase not found</p>
      </div>
    );
  }

  // Support both old and new purchase structures
  const purchaseItems = purchase.purchase_items || purchase.items || [];

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <Link to='../purchases' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Purchases</span>
        </Link>
        {purchase.status === 'pending' && (
          <Button onClick={() => setReceiveOpen(true)}>
            <PackageCheck className='h-4 w-4 mr-2' />
            Verify & Mark Complete
          </Button>
        )}
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            Purchase Details
            <Badge variant='secondary'>ID: {purchase.id}</Badge>
            <Badge variant={purchase.status === 'pending' ? 'secondary' : 'default'}>
              {purchase.status === 'pending' ? 'Pending' : 'Completed'}
            </Badge>
          </CardTitle>
          <CardDescription className='flex items-center gap-2 italic'>
            <span>Datehh: </span>
            <span>{format(new Date(purchase?.created_at), 'PPP')}</span>
          </CardDescription>
        </CardHeader>
        <CardContent className='space-y-6'>
          <div className='grid gap-4 md:grid-cols-2'>
            <div>
              <h3 className='font-semibold mb-2 text-lg'>Purchase Info</h3>
              <div className='space-y-2 text-sm'>
                <p>
                  <span className='font-medium'>Supplier ID:</span> {purchase.supplier_id || 'N/A'}
                </p>
                <p>
                  <span className='font-medium'>Note:</span> {purchase.note || 'No note'}
                </p>
                <p>
                  <span className='font-medium'>Total Amount:</span> {currency}{' '}
                  {parseInt(purchase.total_amount).toLocaleString()}
                </p>
              </div>
            </div>
          </div>

          <div>
            <h3 className='font-semibold mb-4'>Purchase Items</h3>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Cost Price</TableHead>
                  <TableHead>Subtotal</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {purchaseItems && purchaseItems.length > 0 ? (
                  purchaseItems.map((item: any) => (
                    <TableRow key={item.id}>
                      <TableCell className='font-medium'>{item?.product.name}</TableCell>
                      <TableCell>{item.quantity}</TableCell>
                      <TableCell>
                        {currency} {Number(item.cost_price).toLocaleString()}
                      </TableCell>
                      <TableCell>
                        {currency} {Number(item.subtotal).toLocaleString()}
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={4} className='text-center text-muted-foreground'>
                      No items found
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {purchase.status === 'pending' && (
        <ReceivePurchase
          open={receiveOpen}
          onOpenChange={setReceiveOpen}
          purchaseId={purchase.id}
          items={purchaseItems.map((item: any) => ({
            id: item.id,
            product_name: item?.product?.name ?? 'Unknown',
            quantity: item.quantity,
          }))}
          onReceived={refetch}
        />
      )}
    </div>
  );
};
