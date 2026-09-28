import { useBranchDynamicsQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { DollarSign, ShoppingCart, Package, TrendingUp } from 'lucide-react';

export const OperationsOverviewCards = () => {
  const { currencySymbol } = useCurrency();
  const { data: dynamics, isLoading: dynamicsLoading } = useBranchDynamicsQuery();
  const { data: salesData, isLoading: salesLoading } = useGetSalesAnalyticsQuery('last_7_days');
  const { data: purchaseData, isLoading: purchaseLoading } = usePurchaseAnalyticsQuery('last_7_days');
  const { data: productsData, isLoading: productsLoading } = useProductsQuery();

  const isLoading = dynamicsLoading || salesLoading || purchaseLoading || productsLoading;

  const sales = salesData?.data ?? salesData;
  const purchases = purchaseData?.data ?? purchaseData;
  const products = productsData?.products ?? [];

  const totalSales = sales?.total_revenue ?? dynamics?.totalSales ?? 0;
  const totalPurchases = purchases?.total_expenses ?? dynamics?.totalPurchases ?? 0;
  const salesCount = sales?.sales_count ?? sales?.count ?? 0;
  const purchaseCount = purchases?.purchase_count ?? purchases?.count ?? 0;
  const inventoryCount = products.length;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;

  if (isLoading) {
    return (
      <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i}>
            <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
              <Skeleton className='h-4 w-24' />
              <Skeleton className='h-4 w-4' />
            </CardHeader>
            <CardContent>
              <Skeleton className='h-7 w-28' />
              <Skeleton className='mt-2 h-3 w-20' />
            </CardContent>
          </Card>
        ))}
      </div>
    );
  }

  const cards = [
    {
      title: 'Total Sales',
      value: formatAmount(totalSales),
      description: `${salesCount} transactions this period`,
      icon: DollarSign,
      iconClass: 'text-emerald-500',
    },
    {
      title: 'Purchases',
      value: formatAmount(totalPurchases),
      description: `${purchaseCount} transactions this period`,
      icon: ShoppingCart,
      iconClass: 'text-amber-500',
    },
    {
      title: 'Inventory Items',
      value: inventoryCount,
      description: 'Active products in stock',
      icon: Package,
      iconClass: 'text-blue-500',
    },
    {
      title: 'Net Cash Flow',
      value: formatAmount(totalSales - totalPurchases),
      description: 'Sales minus purchases',
      icon: TrendingUp,
      iconClass: totalSales - totalPurchases >= 0 ? 'text-emerald-500' : 'text-red-500',
    },
  ];

  return (
    <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
      {cards.map((card) => {
        const Icon = card.icon;
        return (
          <Card key={card.title}>
            <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
              <CardTitle className='text-sm font-medium'>{card.title}</CardTitle>
              <Icon className={`h-4 w-4 ${card.iconClass}`} />
            </CardHeader>
            <CardContent>
              <div className='text-2xl font-bold'>{card.value}</div>
              <p className='text-xs text-muted-foreground'>{card.description}</p>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};
