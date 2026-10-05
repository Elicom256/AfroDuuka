import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrencyRatesQuery, useDeleteCurrencyRateMutation } from '@/app/store/features/business/executive/currencyRatesQuery';
import { Globe, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

export const ExecutiveCurrencyRatesPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useCurrencyRatesQuery();
  const [deleteRate] = useDeleteCurrencyRateMutation();
  const rates = data?.data || [];

  const handleDelete = async (id: string) => {
    try {
      await deleteRate(id).unwrap();
      toast.success('Rate deleted');
    } catch { toast.error('Failed to delete'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Globe className='h-8 w-8' /> Currency Rates
        </h1>
        <p className='text-muted-foreground'>Exchange rates for multi-currency transactions (base: {currency})</p>
      </div>
      <Card>
        <CardHeader><CardTitle>All Rates</CardTitle></CardHeader>
        <CardContent>
          {rates.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No rates configured.</p>
          ) : (
            <div className='space-y-2'>
              {rates.map((r: any) => (
                <div key={r.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <span className='font-medium'>{currency} → {r.target_currency}: <span className='text-muted-foreground'>{Number(r.rate).toLocaleString()}</span></span>
                  <ConfirmDeleteButton
                    onConfirm={() => handleDelete(r.id)}
                    title='Delete this currency rate?'
                    description='Prices using this rate will no longer convert. This cannot be undone.'
                    trigger={
                      <Button variant='ghost' size='icon'><Trash2 className='h-4 w-4 text-destructive' /></Button>
                    }
                  />
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
