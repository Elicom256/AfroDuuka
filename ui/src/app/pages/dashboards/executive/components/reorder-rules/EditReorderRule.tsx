import { useEffect, useState } from 'react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { toast } from 'sonner';

export const EditReorderRule = ({ open, onOpenChange, rule, products, suppliers, updateRule }: any) => {
  const [form, setForm] = useState({
    reorder_quantity: '',
    preferred_supplier_id: '',
    auto_approve: false,
  });

  useEffect(() => {
    if (rule) {
      setForm({
        reorder_quantity: String(rule.reorder_quantity ?? ''),
        preferred_supplier_id: String(rule.preferred_supplier_id ?? ''),
        auto_approve: Boolean(rule.auto_approve),
      });
    }
  }, [rule]);

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!form.reorder_quantity) {
      toast.error('Reorder quantity is required');
      return;
    }
    try {
      const res = await updateRule({
        id: rule.id,
        ...form,
        reorder_quantity: Number(form.reorder_quantity),
        preferred_supplier_id: form.preferred_supplier_id || null,
      }).unwrap();
      toast.success(res?.message || 'Reorder rule updated');
      onOpenChange(false);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to update');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Reorder Rule</DialogTitle>
          <DialogDescription>Update the reorder rule for {rule?.product?.name}.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Product</Label>
            <div className='text-sm font-medium py-2 px-3 rounded-md border bg-muted'>
              {rule?.product?.name || '—'}
            </div>
          </div>
          <div className='space-y-2'>
            <Label>Reorder Quantity</Label>
            <Input
              type='number'
              value={form.reorder_quantity}
              onChange={(e) => setForm({ ...form, reorder_quantity: e.target.value })}
              placeholder='e.g. 50'
            />
          </div>
          <div className='space-y-2'>
            <Label>Preferred Supplier (optional)</Label>
            <Select
              value={form.preferred_supplier_id}
              onValueChange={(v) => setForm({ ...form, preferred_supplier_id: v })}
            >
              <SelectTrigger>
                <SelectValue placeholder='Select supplier' />
              </SelectTrigger>
              <SelectContent>
                {suppliers?.map((s: any) => (
                  <SelectItem key={s.id} value={String(s.id)}>
                    {s.user?.firstname} {s.user?.lastname}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className='flex items-center gap-3'>
            <Switch checked={form.auto_approve} onCheckedChange={(v) => setForm({ ...form, auto_approve: v })} />
            <Label>Auto-approve purchase orders</Label>
          </div>
          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type='submit'>Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
