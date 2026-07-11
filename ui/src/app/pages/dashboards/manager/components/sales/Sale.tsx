import { Link, useParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useSaleQuery } from '@/app/store/features/branch/sales/salesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeftCircle } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';

export const Sale = () => {
  const { currency } = useCurrency();
  const { id } = useParams<{ id: string }>();
  if (!id) return null;
  const { data: saleData, isLoading: saleLoading } = useSaleQuery(id, { skip: !id });
  if (saleLoading) return <PageLoadingState />;

  const sale = saleData?.sale || saleData;
  // console.log('manager sale==>', sale);

  if (!sale) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Sale not found</p>
      </div>
    );
  }

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Link to='../sales' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Sales</span>
        </Link>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            Sale Details
            <Badge variant='secondary'>ID: {sale.id}</Badge>
          </CardTitle>
          <CardDescription className='flex items-center italic'>
            <span>Date</span>
            <span>{format(new Date(sale.created_at), 'PPP')}</span>
          </CardDescription>
        </CardHeader>
        <CardContent className='space-y-6'>
          <div className='grid gap-4 md:grid-cols-2'>
            <div>
              <h3 className='font-semibold mb-2 text-lg'>Sale Info</h3>
              <div className='space-y-2 text-sm'>
                <p>
                  <span className='font-medium'>Note:</span> {sale.note || 'No note'}
                </p>
                <p>
                  <span className='font-medium'>Total Amount:</span> {currency} {parseInt(sale.total_amount).toLocaleString()}
                </p>
              </div>
            </div>
          </div>

          <div>
            <h3 className='font-semibold mb-4'>Sale Items</h3>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>No</TableHead>
                  <TableHead>Product</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Unit Price ({currency})</TableHead>
                  <TableHead>Total ({currency}) </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sale.sale_items?.map((item: any, i: number) => (
                  <TableRow key={item.id}>
                    <TableCell className='font-medium'>{i + 1}</TableCell>
                    <TableCell className='font-medium'>{item.product.name}</TableCell>
                    <TableCell>{item.quantity}</TableCell>
                    <TableCell> {Number(item.unit_price).toLocaleString()}</TableCell>
                    <TableCell> {Number(item.subtotal).toLocaleString()}</TableCell>
                  </TableRow>
                )) || (
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
