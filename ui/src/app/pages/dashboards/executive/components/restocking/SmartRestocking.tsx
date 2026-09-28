import { useProductRestockingQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertTriangle, Package, TrendingDown } from 'lucide-react';

export const SmartRestocking = () => {
  const { data, isLoading } = useProductRestockingQuery();

  const atRisk = data?.data?.predictions?.filter((p: any) => p.is_at_risk) ?? [];
  const lowStock = data?.data?.predictions?.filter((p: any) => p.is_low_stock) ?? [];
  const atRiskCount = data?.data?.at_risk_count ?? 0;
  const lowStockCount = data?.data?.low_stock_count ?? 0;
  const notSellingCount = data?.data?.not_selling_count ?? 0;

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-40' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
          <Skeleton className='h-4 w-5/6' />
        </CardContent>
      </Card>
    );
  }

  if (atRisk.length === 0 && lowStock.length === 0) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <CardTitle className='flex items-center gap-2 text-sm'>
            <Package className='h-4 w-4 text-green-500' />
            Smart Restocking
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-xs text-muted-foreground'>All stock levels look healthy. No restocking needed.</p>
        </CardContent>
      </Card>
    );
  }

  const topRisks = atRisk.slice(0, 5);

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <TrendingDown className='h-4 w-4 text-amber-500' />
          Smart Restocking
          <Badge variant='destructive' className='ml-auto text-xs'>
            {atRiskCount + lowStockCount} alerts
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-2'>
        {topRisks.map((product: any) => (
          <div
            key={product.id}
            className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2'
          >
            <div className='min-w-0 flex-1'>
              <p className='truncate text-sm font-medium'>{product.name}</p>
              <p className='text-xs text-muted-foreground'>
                Stock: {product.quantity} &middot; ~{product.daily_sales_velocity}/day
              </p>
            </div>
            <Badge
              variant={product.days_until_out !== null && product.days_until_out <= 3 ? 'destructive' : 'outline'}
              className='ml-2 shrink-0 text-xs'
            >
              {product.days_until_out !== null ? `${product.days_until_out}d left` : 'N/A'}
            </Badge>
          </div>
        ))}

        {notSellingCount > 0 && (
          <div className='flex items-center gap-2 rounded-lg bg-muted/50 px-3 py-2 text-xs text-muted-foreground'>
            <AlertTriangle className='h-3 w-3' />
            {notSellingCount} product{notSellingCount > 1 ? 's' : ''} not sold in 30 days
          </div>
        )}

        {lowStockCount > atRiskCount && (
          <p className='text-xs text-muted-foreground'>+{lowStockCount - atRiskCount} more below reorder level</p>
        )}
      </CardContent>
    </Card>
  );
};
