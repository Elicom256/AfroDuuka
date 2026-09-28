import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { useAddFinancialAuditMutation } from '@/app/store/features/audit/financialAuditQuery';

type Props = {
  branches: any[];
};

export const CreateFinancialAudit = ({ branches }: Props) => {
  const [open, setOpen] = useState(false);
  const [addAudit, { isLoading }] = useAddFinancialAuditMutation();
  const [formData, setFormData] = useState({
    business_branch_id: '',
    audit_date: new Date().toISOString().split('T')[0],
    expected_balance: '0',
    actual_balance: '0',
    status: 'draft',
    notes: '',
  });

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!formData.business_branch_id) {
      toast.error('Please select a branch');
      return;
    }
    try {
      await addAudit({
        ...formData,
        expected_balance: Number(formData.expected_balance),
        actual_balance: Number(formData.actual_balance),
      }).unwrap();
      toast.success('Financial audit created');
      setOpen(false);
      setFormData({
        business_branch_id: '',
        audit_date: new Date().toISOString().split('T')[0],
        expected_balance: '0',
        actual_balance: '0',
        status: 'draft',
        notes: '',
      });
    } catch {
      toast.error('Failed to create financial audit');
    }
  };

  const diff = Number(formData.actual_balance) - Number(formData.expected_balance);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button className='rounded-2xl'><Plus className='mr-2 h-4 w-4' /> New Financial Audit</Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Create Financial Audit</DialogTitle>
          <DialogDescription>Record a financial balance verification.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Branch</Label>
              <Select value={formData.business_branch_id} onValueChange={(v) => handleChange('business_branch_id', v)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select branch' />
                </SelectTrigger>
                <SelectContent>
                  {branches.map((b: any) => (
                    <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Date</Label>
              <Input type='date' value={formData.audit_date} onChange={(e) => handleChange('audit_date', e.target.value)} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Expected Balance</Label>
              <Input type='number' min='0' step='0.01' value={formData.expected_balance} onChange={(e) => handleChange('expected_balance', e.target.value)} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Actual Balance</Label>
              <Input type='number' min='0' step='0.01' value={formData.actual_balance} onChange={(e) => handleChange('actual_balance', e.target.value)} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Difference</Label>
              <Input value={`${diff >= 0 ? '+' : ''}${diff}`} className='col-span-3' readOnly />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Status</Label>
              <Select value={formData.status} onValueChange={(v) => handleChange('status', v)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue />
                </SelectTrigger>
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
            <Button type='submit' disabled={isLoading}>{isLoading ? 'Creating...' : 'Create Audit'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
