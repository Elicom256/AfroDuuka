import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useGetReorderSuggestionsQuery } from '@/app/store/features/procurement/procurementQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ReorderSuggestionsPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useGetReorderSuggestionsQuery();

  if (isLoading) return <PageLoadingState />;

  const suggestions = data?.suggestions ?? [];

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <CardTitle>Reorder Suggestions</CardTitle>
          <CardDescription>
            Products that need replenishment based on current stock, reorder levels, and sales velocity.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className='grid gap-4 sm:grid-cols-3 mb-6'>
            <div className='rounded-3xl border border-border/70 bg-muted p-4 text-center'>
              <p className='text-xs uppercase tracking-[0.2em] text-muted-foreground'>Total Suggestions</p>
              <p className='text-2xl font-semibold'>{data?.total_suggestions ?? 0}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-4 text-center'>
              <p className='text-xs uppercase tracking-[0.2em] text-muted-foreground'>Total Estimated Value</p>
              <p className='text-2xl font-semibold'>{currency} {(data?.total_estimated_value ?? 0).toLocaleString()}</p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-muted p-4 text-center'>
              <p className='text-xs uppercase tracking-[0.2em] text-muted-foreground'>Products</p>
              <p className='text-2xl font-semibold'>{suggestions.length}</p>
            </div>
          </div>

          {suggestions.length > 0 ? (
            <div className='space-y-3'>
              {suggestions.map((suggestion) => (
                <div
                  key={suggestion.product_id}
                  className='flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-border/70 bg-muted p-4'
                >
                  <div className='flex-1'>
                    <p className='font-medium'>{suggestion.product_name}</p>
                    <p className='text-xs text-muted-foreground mt-1'>
                      SKU: {suggestion.sku} | Stock: {suggestion.current_stock} | Reorder Level: {suggestion.reorder_level}
                    </p>
                    <p className='text-xs text-muted-foreground'>
                      Avg Daily Sales: {suggestion.avg_daily_sales} | Days Remaining: {suggestion.days_remaining}
                    </p>
                    {suggestion.supplier_name && (
                      <p className='text-xs text-muted-foreground'>Supplier: {suggestion.supplier_name}</p>
                    )}
                  </div>
                  <div className='text-right'>
                    <p className='text-lg font-semibold'>{suggestion.suggested_order_quantity} units</p>
                    <p className='text-sm text-muted-foreground'>
                      {currency} {suggestion.estimated_order_value.toLocaleString()}
                    </p>
                    <p className='text-xs text-muted-foreground'>
                      @ {currency} {suggestion.last_purchase_price.toLocaleString()}/unit
                    </p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className='text-sm text-muted-foreground'>No reorder suggestions at this time.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
