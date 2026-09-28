import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { useApproveFinancialAuditMutation } from '@/app/store/features/audit/financialAuditQuery';

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  audit: any;
};

export const ApproveFinancialAuditDialog = ({ open, onOpenChange, audit }: Props) => {
  const [approveAudit, { isLoading }] = useApproveFinancialAuditMutation();

  const handleApprove = async () => {
    if (!audit) return;
    try {
      await approveAudit(audit.id).unwrap();
      toast.success('Financial audit approved');
      onOpenChange(false);
    } catch {
      toast.error('Failed to approve audit');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Approve Financial Audit</DialogTitle>
          <DialogDescription>
            Confirm the financial audit results. This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div className='space-y-2'>
          <p className='text-sm'><strong>Audit:</strong> {audit?.audit_number}</p>
          <p className='text-sm'><strong>Difference:</strong> {audit?.difference}</p>
        </div>
        <DialogFooter>
          <Button variant='outline' onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={handleApprove} disabled={isLoading}>
            {isLoading ? 'Approving...' : 'Approve'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
