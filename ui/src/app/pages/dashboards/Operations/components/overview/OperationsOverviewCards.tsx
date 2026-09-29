import { useBranchDynamicsQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { DollarSign, ShoppingCart, Package, TrendingUp, ArrowUpRight, ArrowDownRight } from 'lucide-react';

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
  const netCashFlow = totalSales - totalPurchases;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;

  if (isLoading) {
    return (
      <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i} className='rounded-2xl'>
            <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
              <Skeleton className='h-4 w-24' />
              <Skeleton className='h-8 w-8 rounded-xl' />
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
      iconBg: 'bg-emerald-500/10',
      iconClass: 'text-emerald-500',
      trend: sales?.growth_rate ?? sales?.trend ?? null,
    },
    {
      title: 'Purchases',
      value: formatAmount(totalPurchases),
      description: `${purchaseCount} transactions this period`,
      icon: ShoppingCart,
      iconBg: 'bg-amber-500/10',
      iconClass: 'text-amber-500',
      trend: purchases?.growth_rate ?? purchases?.trend ?? null,
    },
    {
      title: 'Inventory Items',
      value: inventoryCount,
      description: 'Active products in stock',
      icon: Package,
      iconBg: 'bg-blue-500/10',
      iconClass: 'text-blue-500',
      trend: null,
    },
    {
      title: 'Net Cash Flow',
      value: formatAmount(netCashFlow),
      description: netCashFlow >= 0 ? 'Looking great!' : 'Needs attention',
      icon: TrendingUp,
      iconBg: netCashFlow >= 0 ? 'bg-emerald-500/10' : 'bg-red-500/10',
      iconClass: netCashFlow >= 0 ? 'text-emerald-500' : 'text-red-500',
      trend: null,
    },
  ];

  return (
    <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
      {cards.map((card) => {
        const Icon = card.icon;
        const TrendIcon = (card.trend ?? 0) >= 0 ? ArrowUpRight : ArrowDownRight;
        return (
          <Card
            key={card.title}
            className='rounded-2xl transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/5'
          >
            <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
              <CardTitle className='text-sm font-medium text-muted-foreground'>{card.title}</CardTitle>
              <div className={`flex h-9 w-9 items-center justify-center rounded-xl ${card.iconBg}`}>
                <Icon className={`h-4 w-4 ${card.iconClass}`} />
              </div>
            </CardHeader>
            <CardContent>
              <div className='text-2xl font-bold tracking-tight'>{card.value}</div>
              <div className='mt-1 flex items-center gap-1.5'>
                {card.trend != null && (
                  <span
                    className={`flex items-center gap-0.5 text-xs font-medium ${
                      card.trend >= 0 ? 'text-emerald-500' : 'text-red-500'
                    }`}
                  >
                    <TrendIcon className='h-3 w-3' />
                    {Math.abs(card.trend).toFixed(1)}%
                  </span>
                )}
                <p className='text-xs text-muted-foreground'>{card.description}</p>
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};
