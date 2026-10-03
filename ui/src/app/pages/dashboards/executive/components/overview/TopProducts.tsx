import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useSalesByProductQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Award } from 'lucide-react';
import { landingPeriodLabel, type LandingPeriod } from '../landingPeriods';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

export const TopProducts = ({ period }: { period: LandingPeriod }) => {
  const { currency } = useCurrency();
  const { data, isLoading, isFetching, isError, refetch } = useSalesByProductQuery(period);

  const topProducts: any[] = data?.data?.top_products ?? [];

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

  if (isError && !data) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <CardTitle className='text-sm'>Top products</CardTitle>
        </CardHeader>
        <CardContent>
          <QueryErrorState
            title='Unable to load top products'
            description='Product sales could not be retrieved.'
            onRetry={refetch}
            retrying={isFetching}
          />
        </CardContent>
      </Card>
    );
  }

  const maxSold = topProducts.length > 0 ? Math.max(...topProducts.map((p) => Number(p.quantity_sold) || 0)) : 1;

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Award className='h-4 w-4 text-primary' />
          Top products
        </CardTitle>
        <CardDescription>Best sellers · {landingPeriodLabel(period)}</CardDescription>
      </CardHeader>
      <CardContent>
        {isError && (
          <QueryErrorState
            title='Top products may be out of date'
            description='The last loaded product rankings are shown.'
            onRetry={refetch}
            retrying={isFetching}
          />
        )}
        {topProducts.length === 0 ? (
          <QueryEmptyState title='No product sales' description='There are no sales in this period.' />
        ) : (
          <div className='space-y-3'>
            {topProducts.slice(0, 5).map((product) => {
              const sold = Number(product.quantity_sold) || 0;
              return (
                <div key={product.product_id} className='space-y-1'>
                  <div className='flex items-center justify-between text-sm'>
                    <span className='truncate font-medium'>{product.product_name}</span>
                    <span className='shrink-0 text-muted-foreground'>
                      {sold} sold · {currency} {Number(product.total_revenue ?? 0).toLocaleString()}
                    </span>
                  </div>
                  <div className='h-1.5 w-full overflow-hidden rounded-full bg-muted'>
                    <div
                      className='h-full rounded-full bg-primary transition-all'
                      style={{ width: `${(sold / maxSold) * 100}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
