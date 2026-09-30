import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Receipt } from 'lucide-react';
import { format } from 'date-fns';

export const RecentSales = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useSalesQuery();

  const sales = data?.sales ?? data ?? [];
  const recent = [...sales]
    .sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5);

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className='h-9 w-full' />
          ))}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Receipt className='h-4 w-4 text-primary' />
          Recent sales
        </CardTitle>
        <CardDescription>Latest transactions</CardDescription>
      </CardHeader>
      <CardContent>
        {recent.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No recent sales.</p>
        ) : (
          <div className='space-y-2'>
            {recent.map((sale: any) => (
              <div key={sale.id} className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2 text-sm'>
                <div className='min-w-0'>
                  <p className='truncate font-medium'>
                    Order #{sale.id}
                    {sale.customer?.name ? ` · ${sale.customer.name}` : ''}
                  </p>
                  <p className='text-xs text-muted-foreground'>{format(new Date(sale.created_at), 'PPp')}</p>
                </div>
                <span className='shrink-0 font-medium text-emerald-600 dark:text-emerald-400'>
                  {currency} {Number(sale.total_amount ?? 0).toLocaleString()}
                </span>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
