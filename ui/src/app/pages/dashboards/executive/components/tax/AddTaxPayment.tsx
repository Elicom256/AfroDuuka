import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import {
  useAddTaxPaymentMutation,
  useTaxCategoriesQuery,
} from '@/app/store/features/business/tax/taxQuery';

type FormState = {
  tax_category_id: string;
  amount: string;
  payment_date: string;
  tax_period_start: string;
  tax_period_end: string;
  reference: string;
  notes: string;
};

const emptyForm: FormState = {
  tax_category_id: '',
  amount: '',
  payment_date: new Date().toISOString().slice(0, 10),
  tax_period_start: '',
  tax_period_end: '',
  reference: '',
  notes: '',
};

export const AddTaxPayment = () => {
  const [open, setOpen] = useState(false);
  const [addPayment] = useAddTaxPaymentMutation();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [formData, setFormData] = useState<FormState>(emptyForm);

  const resetForm = () => setFormData(emptyForm);
  const categories = (categoriesData?.categories ?? []).filter((c: any) => c.is_active);

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await addPayment({
        tax_category_id: Number(formData.tax_category_id),
        amount: Number(formData.amount) || 0,
        payment_date: formData.payment_date,
        tax_period_start: formData.tax_period_start || undefined,
        tax_period_end: formData.tax_period_end || undefined,
        reference: formData.reference || undefined,
        notes: formData.notes || undefined,
      }).unwrap();
      toast.success(res?.message || 'Tax payment recorded');
      setOpen(false);
      resetForm();
    } catch {
      toast.error('Failed to record tax payment');
    }
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { setOpen(o); if (!o) resetForm(); }}>
      <DialogTrigger asChild>
        <Button variant='outline' size='sm'>
          <Plus className='mr-2 h-4 w-4' />
          Record Payment
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Record Tax Payment</DialogTitle>
          <DialogDescription>Log an amount paid to the revenue authority for a tax category.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='tax-category' className='text-right'>Tax Category</Label>
              <Select value={formData.tax_category_id} onValueChange={(v) => setFormData((p) => ({ ...p, tax_category_id: v }))}>
                <SelectTrigger id='tax-category' className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category: any) => (
                    <SelectItem key={category.id} value={String(category.id)}>{category.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='payment-amount' className='text-right'>Amount</Label>
              <Input id='payment-amount' type='number' min='0' step='0.01' value={formData.amount} onChange={(e) => setFormData((p) => ({ ...p, amount: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='payment-date' className='text-right'>Payment Date</Label>
              <Input id='payment-date' type='date' value={formData.payment_date} onChange={(e) => setFormData((p) => ({ ...p, payment_date: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='period-start' className='text-right'>Period From</Label>
              <Input id='period-start' type='date' value={formData.tax_period_start} onChange={(e) => setFormData((p) => ({ ...p, tax_period_start: e.target.value }))} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='period-end' className='text-right'>Period To</Label>
              <Input id='period-end' type='date' value={formData.tax_period_end} onChange={(e) => setFormData((p) => ({ ...p, tax_period_end: e.target.value }))} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='payment-reference' className='text-right'>Reference</Label>
              <Input id='payment-reference' value={formData.reference} onChange={(e) => setFormData((p) => ({ ...p, reference: e.target.value }))} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='payment-notes' className='text-right'>Notes</Label>
              <Textarea id='payment-notes' value={formData.notes} onChange={(e) => setFormData((p) => ({ ...p, notes: e.target.value }))} className='col-span-3' />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Record</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};