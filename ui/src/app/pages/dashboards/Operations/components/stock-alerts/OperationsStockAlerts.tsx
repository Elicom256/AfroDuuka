import { useLowStockQuery, useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useProductExpiringQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertTriangle, PackageX, Clock } from 'lucide-react';

export const OperationsStockAlerts = () => {
  const { data: lowStock, isLoading: lowLoading } = useLowStockQuery('30');
  const { data: outOfStock, isLoading: outLoading } = useOutOfStockQuery('30');
  const { data: expiring, isLoading: expiringLoading } = useProductExpiringQuery();

  const isLoading = lowLoading || outLoading || expiringLoading;

  const lowItems = lowStock?.data ?? [];
  const outItems = outOfStock?.data ?? [];
  const expiringData = expiring?.data;
  const expiringCount = (expiringData?.expiring_count ?? 0) + (expiringData?.expired_count ?? 0);

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

  const totalAlerts = lowItems.length + outItems.length + expiringCount;

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <AlertTriangle className='h-4 w-4 text-amber-500' />
          Stock Alerts
          {totalAlerts > 0 && (
            <Badge variant='destructive' className='ml-auto text-xs'>{totalAlerts}</Badge>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-2'>
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

        {expiringCount > 0 && (
          <div className='flex items-center justify-between rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 dark:border-orange-900/50 dark:bg-orange-950/20'>
            <div className='flex items-center gap-2'>
              <Clock className='h-3.5 w-3.5 text-orange-500' />
              <span className='text-sm font-medium text-orange-700 dark:text-orange-400'>Expiring Soon</span>
            </div>
            <Badge variant='outline' className='border-orange-300 text-xs text-orange-700 dark:border-orange-700 dark:text-orange-400'>
              {expiringCount}
            </Badge>
          </div>
        )}

        {totalAlerts === 0 && (
          <p className='text-xs text-muted-foreground'>No stock alerts. All levels are healthy.</p>
        )}

        {lowItems.length > 0 && (
          <div className='mt-2 space-y-1.5 border-t pt-2'>
            <p className='text-xs font-medium text-muted-foreground'>Low-stock items</p>
            {lowItems.slice(0, 4).map((item: any) => (
              <div key={item.id} className='flex items-center justify-between text-xs'>
                <span className='truncate'>{item.name}</span>
                <span className='ml-2 shrink-0 font-medium text-amber-600 dark:text-amber-400'>
                  {item.quantity} left
                </span>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
