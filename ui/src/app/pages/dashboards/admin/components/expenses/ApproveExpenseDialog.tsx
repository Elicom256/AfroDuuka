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
import { useApproveExpenseMutation } from '@/app/store/features/business/admin/expenseQuery';
import { useCurrency } from '@/app/hooks/useCurrency';

type ApproveExpenseDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expense: any;
};

export const ApproveExpenseDialog = ({ open, onOpenChange, expense }: ApproveExpenseDialogProps) => {
  const [approveExpense] = useApproveExpenseMutation();
  const { currency } = useCurrency();

  const handleApprove = async () => {
    if (!expense?.id) return;
    try {
      const res = await approveExpense(expense.id).unwrap();
      toast.success(res?.message || 'Expense approved');
      onOpenChange(false);
    } catch {
      toast.error('Failed to approve expense');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Approve Expense</DialogTitle>
          <DialogDescription>
            Are you sure you want to approve this expense of {currency} {Number(expense?.amount ?? 0).toLocaleString()}?
          </DialogDescription>
        </DialogHeader>
        <div className='py-2'>
          <p className='text-sm text-muted-foreground'>{expense?.description || 'No description'}</p>
        </div>
        <DialogFooter>
          <Button variant='outline' onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={handleApprove}>Approve</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
