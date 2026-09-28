import { useState } from 'react';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from 'sonner';
import { Plus } from 'lucide-react';
import { format } from 'date-fns';
import { useCreateFinanceAdjustmentMutation } from '@/app/store/features/finance/financeQuery';

type FinanceAdjustmentDialogProps = {
  onSuccess?: () => void;
};

const initialForm = {
  type: 'adjustment',
  amount: '',
  description: '',
  notes: '',
  transaction_date: format(new Date(), 'yyyy-MM-dd'),
};

export const FinanceAdjustmentDialog = ({ onSuccess }: FinanceAdjustmentDialogProps) => {
  const [open, setOpen] = useState(false);
  const [createAdjustment, { isLoading }] = useCreateFinanceAdjustmentMutation();
  const [formData, setFormData] = useState({ ...initialForm });

  const resetForm = () => setFormData({ ...initialForm });

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const payload = {
        ...formData,
        amount: Number(formData.amount),
      };
      const res = await createAdjustment(payload).unwrap();
      toast.success(res?.message || 'Adjustment created successfully');
      setOpen(false);
      resetForm();
      onSuccess?.();
    } catch {
      toast.error('Failed to create adjustment');
    }
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        setOpen(o);
        if (!o) resetForm();
      }}
    >
      <DialogTrigger asChild>
        <Button>
          <Plus className='mr-2 h-4 w-4' />
          Manual Adjustment
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Manual Financial Adjustment</DialogTitle>
          <DialogDescription>
            Record a manual financial adjustment to correct or update your books.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Type</Label>
              <Select
                value={formData.type}
                onValueChange={(v) => setFormData((p) => ({ ...p, type: v }))}
              >
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select type' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='payment_in'>Payment In</SelectItem>
                  <SelectItem value='payment_out'>Payment Out</SelectItem>
                  <SelectItem value='adjustment'>Adjustment</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Amount</Label>
              <Input
                type='number'
                step='0.01'
                min='0'
                value={formData.amount}
                onChange={(e) => setFormData((p) => ({ ...p, amount: e.target.value }))}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Description</Label>
              <Input
                value={formData.description}
                onChange={(e) => setFormData((p) => ({ ...p, description: e.target.value }))}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Notes</Label>
              <Textarea
                value={formData.notes}
                onChange={(e) => setFormData((p) => ({ ...p, notes: e.target.value }))}
                className='col-span-3'
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Transaction Date</Label>
              <Input
                type='date'
                value={formData.transaction_date}
                onChange={(e) => setFormData((p) => ({ ...p, transaction_date: e.target.value }))}
                className='col-span-3'
                required
              />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? 'Saving...' : 'Create Adjustment'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
