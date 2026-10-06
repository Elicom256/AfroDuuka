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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Pencil } from 'lucide-react';
import {
  useTaxCategoriesQuery,
  useUpdateTaxPaymentMutation,
} from '@/app/store/features/business/tax/taxQuery';

type EditTaxPaymentProps = {
  payment: any;
};

type PaymentForm = {
  tax_category_id: string;
  amount: string;
  payment_date: string;
  tax_period_start: string;
  tax_period_end: string;
  reference: string;
  notes: string;
};

export const EditTaxPayment = ({ payment }: EditTaxPaymentProps) => {
  const [open, setOpen] = useState(false);
  const [updatePayment] = useUpdateTaxPaymentMutation();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [formData, setFormData] = useState<PaymentForm>({
    tax_category_id: '',
    amount: '',
    payment_date: '',
    tax_period_start: '',
    tax_period_end: '',
    reference: '',
    notes: '',
  });

  const handleChange = (field: keyof PaymentForm, value: string) =>
    setFormData((prev) => ({ ...prev, [field]: value }));

  const categories = categoriesData?.categories ?? [];

  const handleEdit = () => {
    setFormData({
      tax_category_id: String(payment.tax_category_id ?? ''),
      amount: String(payment.amount ?? ''),
      payment_date: (payment.payment_date ?? '').slice(0, 10),
      tax_period_start: (payment.tax_period_start ?? '').slice(0, 10),
      tax_period_end: (payment.tax_period_end ?? '').slice(0, 10),
      reference: payment.reference ?? '',
      notes: payment.notes ?? '',
    });
    setOpen(true);
  };

  const handleUpdate = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await updatePayment({
        id: payment.id,
        body: {
          tax_category_id: Number(formData.tax_category_id),
          amount: Number(formData.amount) || 0,
          payment_date: formData.payment_date,
          tax_period_start: formData.tax_period_start || undefined,
          tax_period_end: formData.tax_period_end || undefined,
          reference: formData.reference || undefined,
          notes: formData.notes || undefined,
        },
      }).unwrap();
      toast.success(res?.message || 'Tax payment updated');
      setOpen(false);
    } catch {
      toast.error('Failed to update tax payment');
    }
  };

  return (
    <>
      <Button variant='ghost' size='icon' onClick={handleEdit}>
        <Pencil className='h-4 w-4' />
      </Button>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Tax Payment</DialogTitle>
            <DialogDescription>Update the recorded payment details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate}>
            <div className='grid gap-4 py-4'>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='tax-category' className='text-right'>Tax Category</Label>
                <Select value={formData.tax_category_id} onValueChange={(v) => handleChange('tax_category_id', v)}>
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
                <Label htmlFor='edit-payment-amount' className='text-right'>Amount</Label>
                <Input id='edit-payment-amount' type='number' min='0' step='0.01' value={formData.amount} onChange={(e) => handleChange('amount', e.target.value)} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-payment-date' className='text-right'>Payment Date</Label>
                <Input id='edit-payment-date' type='date' value={formData.payment_date} onChange={(e) => handleChange('payment_date', e.target.value)} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-period-start' className='text-right'>Period From</Label>
                <Input id='edit-period-start' type='date' value={formData.tax_period_start} onChange={(e) => handleChange('tax_period_start', e.target.value)} className='col-span-3' />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-period-end' className='text-right'>Period To</Label>
                <Input id='edit-period-end' type='date' value={formData.tax_period_end} onChange={(e) => handleChange('tax_period_end', e.target.value)} className='col-span-3' />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-payment-reference' className='text-right'>Reference</Label>
                <Input id='edit-payment-reference' value={formData.reference} onChange={(e) => handleChange('reference', e.target.value)} className='col-span-3' />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-payment-notes' className='text-right'>Notes</Label>
                <Textarea id='edit-payment-notes' value={formData.notes} onChange={(e) => handleChange('notes', e.target.value)} className='col-span-3' />
              </div>
            </div>
            <DialogFooter>
              <Button type='submit'>Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
};