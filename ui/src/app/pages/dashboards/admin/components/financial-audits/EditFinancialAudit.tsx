import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { useUpdateFinancialAuditMutation } from '@/app/store/features/audit/financialAuditQuery';

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  audit: any;
};

export const EditFinancialAudit = ({ open, onOpenChange, audit }: Props) => {
  const [updateAudit, { isLoading }] = useUpdateFinancialAuditMutation();
  const [formData, setFormData] = useState({
    audit_date: '',
    expected_balance: '0',
    actual_balance: '0',
    status: 'draft',
    notes: '',
  });

  useEffect(() => {
    if (audit) {
      setFormData({
        audit_date: audit.audit_date?.split('T')[0] || '',
        expected_balance: String(audit.expected_balance ?? 0),
        actual_balance: String(audit.actual_balance ?? 0),
        status: audit.status || 'draft',
        notes: audit.notes || '',
      });
    }
  }, [audit]);

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!audit) return;
    try {
      await updateAudit({
        id: audit.id,
        body: {
          ...formData,
          expected_balance: Number(formData.expected_balance),
          actual_balance: Number(formData.actual_balance),
        },
      }).unwrap();
      toast.success('Financial audit updated');
      onOpenChange(false);
    } catch {
      toast.error('Failed to update');
    }
  };

  const diff = Number(formData.actual_balance) - Number(formData.expected_balance);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Edit Financial Audit</DialogTitle>
          <DialogDescription>Update audit details for {audit?.audit_number}.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Date</Label>
              <Input type='date' value={formData.audit_date} onChange={(e) => handleChange('audit_date', e.target.value)} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Expected Balance</Label>
              <Input type='number' min='0' step='0.01' value={formData.expected_balance} onChange={(e) => handleChange('expected_balance', e.target.value)} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Actual Balance</Label>
              <Input type='number' min='0' step='0.01' value={formData.actual_balance} onChange={(e) => handleChange('actual_balance', e.target.value)} className='col-span-3' />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Difference</Label>
              <Input value={`${diff >= 0 ? '+' : ''}${diff}`} className='col-span-3' readOnly />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Status</Label>
              <Select value={formData.status} onValueChange={(v) => handleChange('status', v)}>
                <SelectTrigger className='col-span-3'><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value='draft'>Draft</SelectItem>
                  <SelectItem value='in_progress'>In Progress</SelectItem>
                  <SelectItem value='completed'>Completed</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Notes</Label>
              <Textarea value={formData.notes} onChange={(e) => handleChange('notes', e.target.value)} className='col-span-3' rows={2} />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit' disabled={isLoading}>{isLoading ? 'Updating...' : 'Update Audit'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
