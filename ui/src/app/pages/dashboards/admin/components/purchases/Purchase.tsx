import { Link, useParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePurchaseQuery } from '@/app/store/features/business/purchases/purchasesQuery';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeftCircle } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { format } from 'date-fns';

export const Purchase = () => {
  const { id } = useParams<{ id: string }>();
  const { data: purchaseData, isLoading: purchaseLoading } = usePurchaseQuery(Number(id), { skip: !id });
  const { data: productsData } = useProductsQuery();
  if (purchaseLoading) return <PageLoadingState />;

  const purchase = purchaseData?.purchase || purchaseData;
  const products = productsData?.products || [];
  console.log('Test data==>', purchase);

  if (!purchase) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Purchase not found</p>
      </div>
    );
  }

  const getProductName = (productId: number) => {
    const product = products.find((p: any) => p.id === productId);
    return product?.name || `Product ${productId}`;
  };

  // Support both old and new purchase structures
  const purchaseItems = purchase.purchase_items || purchase.items || [];

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Link to='../purchases' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Purchases</span>
        </Link>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            Purchase Details
            <Badge variant='secondary'>ID: {purchase.id}</Badge>
          </CardTitle>
          <CardDescription className='flex items-center gap-2 italic'>
            <span>Datehh: </span>
            <span>{format(new Date(purchase.created_at), 'PPP')}</span>
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
                  <span className='font-medium'>Total Amount:</span> UGX{' '}
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
                      <TableCell className='font-medium'>{getProductName(item.product_id)}</TableCell>
                      <TableCell>{item.quantity}</TableCell>
                      <TableCell>UGX {Number(item.cost_price).toLocaleString()}</TableCell>
                      <TableCell>UGX {Number(item.subtotal).toLocaleString()}</TableCell>
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
    </div>
  );
};
