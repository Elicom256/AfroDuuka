import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchaseAnalyticsQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { TrendingUp, TrendingDown, DollarSign, ShoppingCart } from 'lucide-react';

export const SalesPurchasesSummary = () => {
  const { currencySymbol } = useCurrency();
  const { data: salesData, isLoading: salesLoading } = useGetSalesAnalyticsQuery('last_7_days');
  const { data: purchaseData, isLoading: purchaseLoading } = usePurchaseAnalyticsQuery('last_7_days');

  const isLoading = salesLoading || purchaseLoading;

  const sales = salesData?.data ?? salesData;
  const purchases = purchaseData?.data ?? purchaseData;

  const totalSales = sales?.total_revenue ?? sales?.total ?? 0;
  const totalPurchases = purchases?.total_expenses ?? purchases?.total ?? 0;
  const salesCount = sales?.sales_count ?? sales?.count ?? 0;
  const purchaseCount = purchases?.purchase_count ?? purchases?.count ?? 0;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;

  if (isLoading) {
    return (
      <Card>
        <CardHeader className="pb-3">
          <Skeleton className="h-5 w-40" />
        </CardHeader>
        <CardContent className="space-y-3">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="flex items-center gap-2 text-sm">
          <DollarSign className="h-4 w-4 text-emerald-500" />
          Sales & Purchases
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-1">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <TrendingUp className="h-3 w-3 text-emerald-500" />
              Sales (7d)
            </div>
            <p className="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
              {formatAmount(totalSales)}
            </p>
            <p className="text-xs text-muted-foreground">{salesCount} transactions</p>
          </div>
          <div className="space-y-1">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <ShoppingCart className="h-3 w-3 text-amber-500" />
              Purchases (7d)
            </div>
            <p className="text-lg font-semibold text-amber-600 dark:text-amber-400">
              {formatAmount(totalPurchases)}
            </p>
            <p className="text-xs text-muted-foreground">{purchaseCount} transactions</p>
          </div>
        </div>
        <div className="mt-3 border-t pt-3">
          <div className="flex items-center justify-between text-xs">
            <span className="text-muted-foreground">Net (Sales - Purchases)</span>
            <span className={`font-medium ${totalSales - totalPurchases >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
              {formatAmount(totalSales - totalPurchases)}
            </span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};
