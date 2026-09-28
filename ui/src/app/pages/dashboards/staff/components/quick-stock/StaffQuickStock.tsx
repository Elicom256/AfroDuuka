import { useLowStockQuery, useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Package, AlertTriangle, PackageX } from 'lucide-react';

export const StaffQuickStock = () => {
  const { data: productsData, isLoading: productsLoading } = useProductsQuery();
  const { data: lowStock, isLoading: lowLoading } = useLowStockQuery('30');
  const { data: outOfStock, isLoading: outLoading } = useOutOfStockQuery('30');

  const isLoading = productsLoading || lowLoading || outLoading;

  const products = productsData?.products ?? [];
  const lowItems = lowStock?.data ?? [];
  const outItems = outOfStock?.data ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Package className='h-4 w-4 text-indigo-500' />
          Quick Stock View
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-2'>
        <div className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2'>
          <span className='text-sm text-muted-foreground'>Total Products</span>
          <Badge variant='secondary' className='text-xs'>{products.length}</Badge>
        </div>

        {outItems.length > 0 && (
          <div className='flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-3 py-2 dark:border-red-900/50 dark:bg-red-950/20'>
            <div className='flex items-center gap-2'>
              <PackageX className='h-3.5 w-3.5 text-red-500' />
              <span className='text-sm font-medium text-red-700 dark:text-red-400'>Out of Stock</span>
            </div>
            <Badge variant='destructive' className='text-xs'>{outItems.length}</Badge>
          </div>
        )}

        {lowItems.length > 0 && (
          <div className='flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/50 dark:bg-amber-950/20'>
            <div className='flex items-center gap-2'>
              <AlertTriangle className='h-3.5 w-3.5 text-amber-500' />
              <span className='text-sm font-medium text-amber-700 dark:text-amber-400'>Low Stock</span>
            </div>
            <Badge variant='outline' className='border-amber-300 text-xs text-amber-700 dark:border-amber-700 dark:text-amber-400'>
              {lowItems.length}
            </Badge>
          </div>
        )}

        {lowItems.length > 0 && (
          <div className='mt-2 space-y-1.5 border-t pt-2'>
            <p className='text-xs font-medium text-muted-foreground'>Needs attention</p>
            {lowItems.slice(0, 3).map((item: any) => (
              <div key={item.id} className='flex items-center justify-between text-xs'>
                <span className='truncate'>{item.name}</span>
                <span className='ml-2 shrink-0 font-medium text-amber-600 dark:text-amber-400'>
                  {item.quantity} left
                </span>
              </div>
            ))}
          </div>
        )}

        {outItems.length === 0 && lowItems.length === 0 && (
          <p className='text-xs text-muted-foreground'>All stock levels are healthy.</p>
        )}
      </CardContent>
    </Card>
  );
};
