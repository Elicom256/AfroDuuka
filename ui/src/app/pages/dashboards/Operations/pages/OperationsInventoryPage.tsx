import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useProductsQuery, useProductRestockingQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ProductTable } from '../components/products/ProductTable';
import { AdjustStock } from '../components/products/AdjustStock';
import { OperationsStockAlerts } from '../components/stock-alerts/OperationsStockAlerts';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Package, TrendingUp, AlertTriangle, DollarSign } from 'lucide-react';

export const OperationsInventoryPage = () => {
  const { currency } = useCurrency();
  const { data: productData, isLoading } = useProductsQuery();
  const { data: restocking } = useProductRestockingQuery();
  const [adjustProduct, setAdjustProduct] = useState<any>(null);

  if (isLoading) return <PageLoadingState />;

  const products = productData?.products ?? [];
  const totalProducts = products.length;
  const totalValue = products.reduce((sum: number, p: any) => sum + (Number(p.selling_price) * Number(p.quantity)), 0);
  const totalItems = products.reduce((sum: number, p: any) => sum + Number(p.quantity), 0);
  const lowStockCount = restocking?.data?.low_stock_count ?? 0;
  const atRiskCount = restocking?.data?.at_risk_count ?? 0;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Inventory</h1>
        <p className='text-muted-foreground'>Track inventory levels for your branch</p>
      </div>

      <div className='grid gap-4 sm:grid-cols-2 xl:grid-cols-4'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-blue-500/10 p-2.5'>
              <Package className='h-5 w-5 text-blue-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Products</p>
              <p className='text-xl font-semibold'>{totalProducts}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-green-500/10 p-2.5'>
              <DollarSign className='h-5 w-5 text-green-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Stock Value</p>
              <p className='text-xl font-semibold'>{currency} {totalValue.toLocaleString()}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-purple-500/10 p-2.5'>
              <TrendingUp className='h-5 w-5 text-purple-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Items</p>
              <p className='text-xl font-semibold'>{totalItems.toLocaleString()}</p>
            </div>
          </div>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <div className='flex items-center gap-3'>
            <div className='rounded-2xl bg-amber-500/10 p-2.5'>
              <AlertTriangle className='h-5 w-5 text-amber-500' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Low Stock</p>
              <p className='text-xl font-semibold'>{lowStockCount + atRiskCount}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className='grid gap-6 lg:grid-cols-3'>
        <div className='lg:col-span-2'>
          <Card className='rounded-3xl border border-border/70 bg-card p-3'>
            <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
              <div>
                <CardTitle>Products</CardTitle>
                <CardDescription>Manage your branch inventory</CardDescription>
              </div>
              <Button onClick={() => setAdjustProduct(products[0])} disabled={products.length === 0}>
                Adjust Stock
              </Button>
            </CardHeader>
            <CardContent>
              <ProductTable />
            </CardContent>
          </Card>
        </div>
        <div>
          <OperationsStockAlerts />
        </div>
      </div>

      {adjustProduct && (
        <AdjustStock open={Boolean(adjustProduct)} onOpenChange={(open) => { if (!open) setAdjustProduct(null); }} product={adjustProduct} />
      )}
    </div>
  );
};
