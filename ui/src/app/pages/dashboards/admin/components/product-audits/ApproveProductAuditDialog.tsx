import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { useApproveProductAuditMutation } from '@/app/store/features/audit/productAuditQuery';

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  audit: any;
};

export const ApproveProductAuditDialog = ({ open, onOpenChange, audit }: Props) => {
  const [approveAudit, { isLoading }] = useApproveProductAuditMutation();

  const handleApprove = async () => {
    if (!audit) return;
    try {
      await approveAudit(audit.id).unwrap();
      toast.success('Product audit approved and inventory adjusted');
      onOpenChange(false);
    } catch {
      toast.error('Failed to approve audit');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Approve Product Audit</DialogTitle>
          <DialogDescription>
            This will adjust inventory quantities for all items with differences. This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div className='space-y-2'>
          <p className='text-sm'><strong>Audit:</strong> {audit?.audit_number}</p>
          <p className='text-sm'><strong>Items to adjust:</strong> {(audit?.items || []).filter((i: any) => i.difference !== 0).length}</p>
        </div>
        <DialogFooter>
          <Button variant='outline' onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={handleApprove} disabled={isLoading}>
            {isLoading ? 'Approving...' : 'Approve & Adjust Inventory'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
