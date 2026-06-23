import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';

export const AddCurrencyRate = ({ createRate }: any) => {
  const { currency } = useCurrency();
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ target_currency: '', rate: '' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.target_currency || !form.rate) { toast.error('Fill all fields'); return; }
    try {
      await createRate({ target_currency: form.target_currency.toUpperCase(), rate: form.rate, source: 'manual' }).unwrap();
      toast.success('Rate added');
      setOpen(false);
      setForm({ target_currency: '', rate: '' });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Add Rate</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Exchange Rate</DialogTitle>
          <DialogDescription>Base currency is {currency}. Enter the target currency and its rate.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Target Currency</Label>
            <Input value={form.target_currency} onChange={(e) => setForm({ ...form, target_currency: e.target.value })} placeholder='e.g. USD' maxLength={3} className='uppercase' />
          </div>
          <div className='space-y-2'>
            <Label>Rate (1 {form.target_currency.toUpperCase() || 'CURRENCY'} = ? {currency})</Label>
            <Input type='number' step='0.000001' value={form.rate} onChange={(e) => setForm({ ...form, rate: e.target.value })} placeholder='e.g. 3700' />
          </div>
          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>Cancel</Button>
            <Button type='submit'>Save</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
