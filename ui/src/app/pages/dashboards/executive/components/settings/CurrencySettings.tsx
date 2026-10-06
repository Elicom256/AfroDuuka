import { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Globe, Loader2, Plus, Trash2 } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';
import {
  useCurrencyRatesQuery,
  useCreateCurrencyRateMutation,
  useDeleteCurrencyRateMutation,
} from '@/app/store/features/business/executive/currencyRatesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

export const CurrencySettings = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useCurrencyRatesQuery();
  const [createRate, { isLoading: isCreating }] = useCreateCurrencyRateMutation();
  const [deleteRate] = useDeleteCurrencyRateMutation();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ target_currency: '', rate: '' });

  const rates = data?.data || [];

  const handleCreate = async () => {
    if (!form.target_currency || !form.rate) {
      toast.error('Please fill all fields');
      return;
    }
    try {
      await createRate({ target_currency: form.target_currency.toUpperCase(), rate: form.rate, source: 'manual' }).unwrap();
      toast.success('Currency rate added');
      setForm({ target_currency: '', rate: '' });
      setShowForm(false);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to add rate');
    }
  };

  const handleDelete = async (id: string) => {
    try {
      await deleteRate(id).unwrap();
      toast.success('Currency rate deleted');
    } catch {
      toast.error('Failed to delete');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h3 className='text-2xl font-semibold tracking-tight flex items-center gap-3'>
          <Globe className='h-7 w-7 text-primary' />
          Currency Settings
        </h3>
        <p className='text-muted-foreground mt-1'>Manage exchange rates for multi-currency transactions</p>
      </div>

      <Card className='border-border/70'>
        <CardContent className='p-6'>
          <div className='flex items-center justify-between mb-4'>
            <p className='font-semibold'>Exchange Rates (Base: {currency})</p>
            <Button size='sm' onClick={() => setShowForm(!showForm)}>
              <Plus className='h-4 w-4 mr-1' /> Add Rate
            </Button>
          </div>

          {showForm && (
            <div className='flex gap-3 mb-4 p-4 bg-muted rounded-lg'>
              <Input id='usd'
                placeholder='USD'
                value={form.target_currency}
                onChange={(e) => setForm({ ...form, target_currency: e.target.value })}
                className='w-24 uppercase'
                maxLength={3}
              />
              <Input id='rate'
                placeholder='Rate'
                type='number'
                step='0.000001'
                value={form.rate}
                onChange={(e) => setForm({ ...form, rate: e.target.value })}
                className='flex-1'
              />
              <Button size='sm' onClick={handleCreate} disabled={isCreating}>
                {isCreating && <Loader2 className='h-4 w-4 animate-spin mr-1' />}
                Save
              </Button>
            </div>
          )}

          {rates.length === 0 ? (
            <p className='text-muted-foreground text-sm py-4 text-center'>No exchange rates configured yet.</p>
          ) : (
            <div className='space-y-2'>
              {rates.map((rate: any) => (
                <div key={rate.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <div>
                    <span className='font-medium'>{currency} → {rate.target_currency}</span>
                    <span className='ml-4 text-muted-foreground'>1 {rate.target_currency} = {Number(rate.rate).toLocaleString()} {currency}</span>
                  </div>
                  <ConfirmDeleteButton
                    onConfirm={() => handleDelete(rate.id)}
                    title='Delete this currency rate?'
                    description='Prices using this rate will no longer convert. This cannot be undone.'
                    trigger={
                      <Button variant='ghost' size='icon'>
                                          <Trash2 className='h-4 w-4 text-destructive' />
                                        </Button>
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

export default CurrencySettings;
