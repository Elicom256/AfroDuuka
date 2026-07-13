import { useEffect, useState } from 'react';
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
import { format } from 'date-fns';
import { useUpdateExpenseMutation } from '@/app/store/features/business/admin/expenseQuery';

type EditExpenseProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expense: any;
  categories: any[];
};

export const EditExpense = ({ open, onOpenChange, expense, categories }: EditExpenseProps) => {
  const [updateExpense] = useUpdateExpenseMutation();
  const [formData, setFormData] = useState({
    expense_category_id: '',
    amount: '',
    vendor: '',
    description: '',
    payment_date: format(new Date(), 'yyyy-MM-dd'),
  });

  useEffect(() => {
    if (expense) {
      setFormData({
        expense_category_id: String(expense.expense_category_id ?? ''),
        amount: String(expense.amount ?? ''),
        vendor: expense.vendor ?? '',
        description: expense.description ?? '',
        payment_date: expense.payment_date ? format(new Date(expense.payment_date), 'yyyy-MM-dd') : format(new Date(), 'yyyy-MM-dd'),
      });
    }
  }, [expense]);

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!expense?.id) return;
    try {
      const res = await updateExpense({ id: expense.id, body: formData }).unwrap();
      toast.success(res?.message || 'Expense updated');
      onOpenChange(false);
    } catch {
      toast.error('Failed to update expense');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Edit Expense</DialogTitle>
          <DialogDescription>Update the expense details.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Category</Label>
              <Select value={formData.expense_category_id} onValueChange={(v) => setFormData((p) => ({ ...p, expense_category_id: v }))}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((cat: any) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Amount</Label>
              <Input type='number' step='0.01' value={formData.amount} onChange={(e) => setFormData((p) => ({ ...p, amount: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Vendor</Label>
              <Input value={formData.vendor} onChange={(e) => setFormData((p) => ({ ...p, vendor: e.target.value }))} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Description</Label>
              <Textarea value={formData.description} onChange={(e) => setFormData((p) => ({ ...p, description: e.target.value }))} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Payment Date</Label>
              <Input type='date' value={formData.payment_date} onChange={(e) => setFormData((p) => ({ ...p, payment_date: e.target.value }))} className='col-span-3' required />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
