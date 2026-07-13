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
import { format } from 'date-fns';
import { useAddExpenseMutation } from '@/app/store/features/business/admin/expenseQuery';

type AddExpenseProps = {
  categories: any[];
  branches: any[];
};

export const AddExpense = ({ categories, branches }: AddExpenseProps) => {
  const [open, setOpen] = useState(false);
  const [addExpense] = useAddExpenseMutation();
  const [formData, setFormData] = useState({
    expense_category_id: '',
    amount: '',
    business_branch_id: '',
    vendor: '',
    description: '',
    payment_date: format(new Date(), 'yyyy-MM-dd'),
  });

  const resetForm = () => {
    setFormData({
      expense_category_id: '',
      amount: '',
      business_branch_id: '',
      vendor: '',
      description: '',
      payment_date: format(new Date(), 'yyyy-MM-dd'),
    });
  };

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await addExpense(formData).unwrap();
      toast.success(res?.message || 'Expense created');
      setOpen(false);
      resetForm();
    } catch {
      toast.error('Failed to create expense');
    }
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { setOpen(o); if (!o) resetForm(); }}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='mr-2 h-4 w-4' />
          Add Expense
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Record Expense</DialogTitle>
          <DialogDescription>Enter the details of the expense.</DialogDescription>
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
              <Label className='text-right'>Branch</Label>
              <Select value={formData.business_branch_id} onValueChange={(v) => setFormData((p) => ({ ...p, business_branch_id: v }))}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select branch (optional)' />
                </SelectTrigger>
                <SelectContent>
                  {branches.map((b: any) => (
                    <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
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
            <Button type='submit'>Record Expense</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
