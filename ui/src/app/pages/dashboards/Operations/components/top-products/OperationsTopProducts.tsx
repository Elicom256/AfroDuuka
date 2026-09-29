import { useProductAnalyticsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Trophy } from 'lucide-react';

export const OperationsTopProducts = () => {
  const { data, isLoading } = useProductAnalyticsQuery();

  if (isLoading) {
    return (
      <Card className='rounded-2xl'>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className='flex items-center gap-3'>
              <Skeleton className='h-8 w-8 rounded-lg' />
              <div className='flex-1 space-y-1'>
                <Skeleton className='h-3 w-24' />
                <Skeleton className='h-2 w-full' />
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    );
  }

  const analyticsData = data?.data ?? data;
  const topProducts = Array.isArray(analyticsData?.top_products)
    ? analyticsData.top_products
    : Array.isArray(analyticsData?.top_selling)
      ? analyticsData.top_selling
      : Array.isArray(analyticsData)
        ? analyticsData
        : [];

  const maxRevenue = topProducts.length > 0
    ? Math.max(...topProducts.map((p: any) => Number(p.revenue ?? p.total_revenue ?? p.sales ?? 0)))
    : 1;

  return (
    <Card className='rounded-2xl'>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Trophy className='h-4 w-4 text-yellow-500' />
          Top Products
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-3'>
        {topProducts.length === 0 && (
          <p className='text-xs text-muted-foreground'>No product data yet. Start selling to see your top products here.</p>
        )}
        {topProducts.slice(0, 5).map((product: any, index: number) => {
          const name = product.name ?? product.product_name ?? 'Unknown Product';
          const revenue = Number(product.revenue ?? product.total_revenue ?? product.sales ?? 0);
          const percentage = maxRevenue > 0 ? (revenue / maxRevenue) * 100 : 0;

          return (
            <div key={product.id ?? index} className='space-y-1'>
              <div className='flex items-center justify-between text-xs'>
                <div className='flex items-center gap-2'>
                  <span className='flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-[10px] font-bold text-primary'>
                    {index + 1}
                  </span>
                  <span className='font-medium truncate max-w-[120px]'>{name}</span>
                </div>
                <span className='text-muted-foreground font-medium'>{Math.round(revenue).toLocaleString()}</span>
              </div>
              <div className='h-1.5 w-full rounded-full bg-muted overflow-hidden'>
                <div
                  className='h-full rounded-full bg-linear-to-r from-primary to-primary/60 transition-all duration-500'
                  style={{ width: `${percentage}%` }}
                />
              </div>
            </div>
          );
        })}
      </CardContent>
    </Card>
  );
};
