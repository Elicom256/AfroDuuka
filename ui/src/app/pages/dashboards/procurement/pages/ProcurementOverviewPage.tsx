import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useGetOverviewQuery } from '@/app/store/features/procurement/procurementQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';
import { AlertTriangle, Package, Truck, DollarSign } from 'lucide-react';

export const ProcurementOverviewPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useGetOverviewQuery();

  if (isLoading) return <PageLoadingState />;

  const overview = data;

  const statCards = [
    {
      title: 'Products Needing Reorder',
      value: overview?.products_needing_reorder ?? 0,
      icon: Package,
      description: 'Items at or below reorder level',
    },
    {
      title: 'Critical Stock',
      value: overview?.critical_stock ?? 0,
      icon: AlertTriangle,
      description: 'Items with 5 or fewer units',
    },
    {
      title: 'Suggested Purchase Value',
      value: `${currency} ${(overview?.suggested_purchase_value ?? 0).toLocaleString()}`,
      icon: DollarSign,
      description: 'Estimated cost of suggested orders',
    },
    {
      title: 'Pending Orders',
      value: overview?.pending_purchase_orders ?? 0,
      icon: Truck,
      description: 'Draft, pending, or approved orders',
    },
  ];

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Procurement Overview</CardTitle>
          <CardDescription>Monitor stock levels, pending orders, and reorder suggestions.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-4'>
            {statCards.map((stat) => {
              const Icon = stat.icon;
              return (
                <div key={stat.title} className='rounded-3xl border border-border/70 bg-muted p-4'>
                  <div className='flex items-center gap-2 mb-2'>
                    <Icon className='h-4 w-4 text-muted-foreground' />
                    <p className='text-xs uppercase tracking-[0.2em] text-muted-foreground'>{stat.title}</p>
                  </div>
                  <p className='text-2xl font-semibold'>{stat.value}</p>
                  <p className='text-xs text-muted-foreground mt-1'>{stat.description}</p>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Recent Reorder Suggestions</CardTitle>
          <CardDescription>Products that need replenishment based on sales velocity.</CardDescription>
        </CardHeader>
        <CardContent>
          {overview?.reorder_suggestions && overview.reorder_suggestions.length > 0 ? (
            <div className='space-y-3'>
              {overview.reorder_suggestions.slice(0, 5).map((suggestion) => (
                <div key={suggestion.product_id} className='flex items-center justify-between rounded-2xl border border-border/70 bg-muted p-4'>
                  <div>
                    <p className='font-medium'>{suggestion.product_name}</p>
                    <p className='text-xs text-muted-foreground'>
                      Stock: {suggestion.current_stock} / Reorder: {suggestion.reorder_level} | Avg Daily Sales: {suggestion.avg_daily_sales}
                    </p>
                  </div>
                  <div className='text-right'>
                    <p className='font-semibold'>{suggestion.suggested_order_quantity} units</p>
                    <p className='text-xs text-muted-foreground'>{currency} {suggestion.estimated_order_value.toLocaleString()}</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className='text-sm text-muted-foreground'>No reorder suggestions at this time.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
