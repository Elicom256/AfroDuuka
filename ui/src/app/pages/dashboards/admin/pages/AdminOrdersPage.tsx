import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Package, Eye } from 'lucide-react';
import { useOrdersQuery } from '@/app/store/features/orders/ordersQuery';
import { useState } from 'react';

const statusStyles: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  processing: 'bg-indigo-100 text-indigo-800',
  shipped: 'bg-purple-100 text-purple-800',
  delivered: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
};

export const AdminOrdersPage = () => {
  const { data, isLoading } = useOrdersQuery();
  const [selectedOrder, setSelectedOrder] = useState<any>(null);
  const orders = data?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <Card className='border-border/60'>
        <CardHeader>
          <CardTitle className='text-lg flex items-center gap-2'>
            <Package className='h-5 w-5' />
            Orders
          </CardTitle>
        </CardHeader>
        <CardContent className='p-0'>
          {orders.length === 0 ? (
            <div className='flex flex-col items-center justify-center gap-3 px-6 py-12 text-center'>
              <div className='rounded-full bg-muted p-3'>
                <Package className='h-5 w-5 text-muted-foreground' />
              </div>
              <p className='font-medium'>No orders yet</p>
              <p className='text-sm text-muted-foreground'>Orders will appear here once customers place them.</p>
            </div>
          ) : (
            <div className='overflow-x-auto'>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Order #</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Items</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead className='text-right'>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {orders.map((order: any) => (
                    <TableRow key={order.id}>
                      <TableCell className='font-mono font-medium'>{order.order_number}</TableCell>
                      <TableCell>{order.customer?.name || 'Walk-in'}</TableCell>
                      <TableCell>{order.items?.length || 0}</TableCell>
                      <TableCell>{Number(order.total_amount).toLocaleString()}</TableCell>
                      <TableCell>
                        <Badge className={statusStyles[order.status] || ''}>{order.status}</Badge>
                      </TableCell>
                      <TableCell className='text-sm text-muted-foreground'>
                        {new Date(order.created_at).toLocaleDateString()}
                      </TableCell>
                      <TableCell className='text-right'>
                        <Button variant='ghost' size='sm' onClick={() => setSelectedOrder(order)}>
                          <Eye className='h-4 w-4' />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {selectedOrder && (
        <div
          className='fixed inset-0 bg-black/50 flex items-center justify-center z-50'
          onClick={() => setSelectedOrder(null)}
        >
          <div
            className='bg-card rounded-3xl border border-border p-6 w-125 max-h-[80vh] overflow-y-auto'
            onClick={(e) => e.stopPropagation()}
          >
            <div className='flex items-center justify-between mb-4'>
              <h2 className='text-lg font-semibold'>Order {selectedOrder.order_number}</h2>
              <Button variant='ghost' size='sm' onClick={() => setSelectedOrder(null)}>
                Close
              </Button>
            </div>
            <div className='space-y-2 mb-4'>
              <p className='text-sm'><span className='text-muted-foreground'>Status:</span> {selectedOrder.status}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Customer:</span> {selectedOrder.customer?.name || 'Walk-in'}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Date:</span> {new Date(selectedOrder.created_at).toLocaleString()}</p>
              {selectedOrder.notes && <p className='text-sm'><span className='text-muted-foreground'>Notes:</span> {selectedOrder.notes}</p>}
            </div>
            <div className='border-t border-border pt-3'>
              <h3 className='text-sm font-semibold mb-2'>Items</h3>
              <div className='space-y-2'>
                {selectedOrder.items?.map((item: any) => (
                  <div key={item.id} className='flex justify-between text-sm'>
                    <span>{item.product?.name || `Product #${item.product_id}`} x{item.quantity}</span>
                    <span>{Number(item.subtotal).toLocaleString()}</span>
                  </div>
                ))}
              </div>
              <div className='flex justify-between font-bold text-lg border-t border-border pt-2 mt-2'>
                <span>Total</span>
                <span>{Number(selectedOrder.total_amount).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
