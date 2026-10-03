import { useLowStockQuery, useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useProductExpiringQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertTriangle, PackageSearch } from 'lucide-react';
import { QueryErrorState } from '@/app/components/QueryErrorState';

type StockWatchItem = {
  id: number | string;
  name: string;
  quantity?: number | string;
  status: string;
};

export const StockHealth = () => {
  const {
    data: lowStock,
    isLoading: lowLoading,
    isFetching: lowFetching,
    isError: lowError,
    refetch: refetchLow,
  } = useLowStockQuery('30');
  const {
    data: outOfStock,
    isLoading: outLoading,
    isFetching: outFetching,
    isError: outError,
    refetch: refetchOut,
  } = useOutOfStockQuery('30');
  const {
    data: expiring,
    isLoading: expiringLoading,
    isFetching: expiringFetching,
    isError: expiringError,
    refetch: refetchExpiring,
  } = useProductExpiringQuery();

  const isLoading = lowLoading || outLoading || expiringLoading;

  const lowItems: Omit<StockWatchItem, 'status'>[] = Array.isArray(lowStock?.data?.products)
    ? lowStock.data.products
    : [];
  const outItems: Omit<StockWatchItem, 'status'>[] = Array.isArray(outOfStock?.data?.products)
    ? outOfStock.data.products
    : [];
  const lowCount = Number(lowStock?.data?.low_stock_count ?? lowItems.length);
  const outCount = Number(outOfStock?.data?.out_of_stock_count ?? outItems.length);
  const expiringData = expiring?.data;
  const expiringCount = (expiringData?.expiring_count ?? 0) + (expiringData?.expired_count ?? 0);
  const watchItems = [
    ...outItems.map((item) => ({ ...item, status: 'Out of stock' })),
    ...lowItems.map((item) => ({ ...item, status: 'Low stock' })),
  ].slice(0, 5);

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
          <Skeleton className='h-4 w-5/6' />
        </CardContent>
      </Card>
    );
  }

  const hasErrors = lowError || outError || expiringError;
  const hasMissingFailedData = (lowError && !lowStock) || (outError && !outOfStock) || (expiringError && !expiring);
  if (hasMissingFailedData) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <CardTitle className='text-sm'>Stock to watch</CardTitle>
        </CardHeader>
        <CardContent className='space-y-3'>
          {lowError && !lowStock && (
            <QueryErrorState
              title='Low-stock alerts unavailable'
              description='The low-stock report failed to load.'
              onRetry={refetchLow}
              retrying={lowFetching}
            />
          )}
          {outError && !outOfStock && (
            <QueryErrorState
              title='Out-of-stock alerts unavailable'
              description='The out-of-stock report failed to load.'
              onRetry={refetchOut}
              retrying={outFetching}
            />
          )}
          {expiringError && !expiring && (
            <QueryErrorState
              title='Expiry alerts unavailable'
              description='Product expiry data failed to load.'
              onRetry={refetchExpiring}
              retrying={expiringFetching}
            />
          )}
        </CardContent>
      </Card>
    );
  }

  const totalAlerts = lowCount + outCount + expiringCount;

  return (
    <Card className='h-full'>
      <CardHeader className='flex flex-row items-center justify-between pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <PackageSearch className='h-4 w-4 text-primary' />
          Stock to watch
          {totalAlerts > 0 && (
            <Badge variant='secondary' className='ml-auto text-xs'>
              {totalAlerts} items
            </Badge>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {hasErrors && (
          <div className='mb-3 space-y-2'>
            {lowError && (
              <QueryErrorState
                title='Low-stock data may be out of date'
                description='The last loaded results are shown.'
                onRetry={refetchLow}
                retrying={lowFetching}
              />
            )}
            {outError && (
              <QueryErrorState
                title='Out-of-stock data may be out of date'
                description='The last loaded results are shown.'
                onRetry={refetchOut}
                retrying={outFetching}
              />
            )}
            {expiringError && (
              <QueryErrorState
                title='Expiry data may be out of date'
                description='The last loaded results are shown.'
                onRetry={refetchExpiring}
                retrying={expiringFetching}
              />
            )}
          </div>
        )}
        <div className='mb-3 grid grid-cols-3 divide-x divide-border border-y border-border py-2 text-center'>
          <div>
            <p className='text-lg font-semibold'>{outCount}</p>
            <p className='text-[10px] text-muted-foreground'>Out of stock</p>
          </div>
          <div>
            <p className='text-lg font-semibold'>{lowCount}</p>
            <p className='text-[10px] text-muted-foreground'>Low stock</p>
          </div>
          <div>
            <p className='text-lg font-semibold'>{expiringCount}</p>
            <p className='text-[10px] text-muted-foreground'>Expiring</p>
          </div>
        </div>

        {watchItems.length > 0 ? (
          <div className='divide-y divide-border'>
            {watchItems.map((item, index) => (
              <div
                key={`${item.id}-${item.status}-${index}`}
                className='flex items-center justify-between gap-3 py-2.5'
              >
                <div className='flex min-w-0 items-center gap-2'>
                  {item.status === 'Out of stock' ? (
                    <PackageSearch className='h-3.5 w-3.5 shrink-0 text-destructive' />
                  ) : (
                    <AlertTriangle className='h-3.5 w-3.5 shrink-0 text-amber-500' />
                  )}
                  <span className='truncate text-xs font-medium'>{item.name}</span>
                </div>
                <span
                  className={`shrink-0 text-[10px] ${item.status === 'Out of stock' ? 'text-destructive' : 'text-amber-500'}`}
                >
                  {item.status === 'Out of stock' ? item.status : `${item.quantity} left`}
                </span>
              </div>
            ))}
          </div>
        ) : (
          <p className='py-4 text-center text-xs text-muted-foreground'>No low or out-of-stock items.</p>
        )}

        {expiringCount > 0 && (
          <p className='mt-2 border-t border-border pt-2 text-xs text-amber-500'>
            {expiringCount} product{expiringCount === 1 ? '' : 's'} expiring or expired
          </p>
        )}
      </CardContent>
    </Card>
  );
};
