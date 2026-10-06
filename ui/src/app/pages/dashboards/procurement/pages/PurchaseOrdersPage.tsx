import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useGetPurchaseOrdersQuery } from '@/app/store/features/procurement/procurementQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Badge } from '@/components/ui/badge';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

const statusColors: Record<string, string> = {
  draft: 'bg-gray-500/10 text-muted-foreground',
  pending: 'bg-yellow-500/10 text-yellow-500',
  approved: 'bg-blue-500/10 text-blue-500',
  ordered: 'bg-purple-500/10 text-purple-500',
  partially_received: 'bg-orange-500/10 text-orange-500',
  received: 'bg-green-500/10 text-green-500',
  cancelled: 'bg-red-500/10 text-red-500',
};

export const PurchaseOrdersPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading, isFetching, isError, refetch } = useGetPurchaseOrdersQuery();

  if (isLoading) return <PageLoadingState />;
  if (isError && !data) {
    return (
      <QueryErrorState
        title='Unable to load purchase orders'
        description='Purchase order records are unavailable because the request failed.'
        onRetry={refetch}
        retrying={isFetching}
      />
    );
  }

  const orders = data?.data ?? [];

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Purchase Orders</CardTitle>
          <CardDescription>Manage purchase orders from draft through receiving.</CardDescription>
        </CardHeader>
        <CardContent>
          {isError && (
            <QueryErrorState
              title='Purchase orders may be out of date'
              description='The latest refresh failed. The last loaded orders are still shown.'
              onRetry={refetch}
              retrying={isFetching}
            />
          )}
          {orders.length > 0 ? (
            <div className='space-y-3'>
              {orders.map((order) => (
                <div
                  key={order.id}
                  className='flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-border/70 bg-muted p-4'
                >
                  <div className='flex-1'>
                    <div className='flex items-center gap-2'>
                      <p className='font-medium'>{order.order_number}</p>
                      <Badge className={statusColors[order.status] || 'bg-gray-500/10 text-muted-foreground'}>
                        {order.status.replace(/_/g, ' ')}
                      </Badge>
                    </div>
                    <p className='text-xs text-muted-foreground mt-1'>
                      Supplier: {order.supplier?.company_name ?? 'N/A'} | Items: {order.items?.length ?? 0}
                    </p>
                    {order.expected_delivery_date && (
                      <p className='text-xs text-muted-foreground'>
                        Expected: {new Date(order.expected_delivery_date).toLocaleDateString()}
                      </p>
                    )}
                  </div>
                  <div className='text-right'>
                    <p className='text-lg font-semibold'>
                      {currency} {Number(order.total_amount).toLocaleString()}
                    </p>
                    <p className='text-xs text-muted-foreground'>{new Date(order.created_at).toLocaleDateString()}</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <QueryEmptyState
              title='No purchase orders yet'
              description='Purchase orders will appear here once created.'
            />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
