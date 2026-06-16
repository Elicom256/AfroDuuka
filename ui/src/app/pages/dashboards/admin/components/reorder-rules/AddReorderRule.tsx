import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export const AddReorderRule = ({ createRule, products, suppliers }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ business_branch_product_id: '', reorder_quantity: '', preferred_supplier_id: '', auto_approve: false });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.business_branch_product_id || !form.reorder_quantity) { toast.error('Product and quantity required'); return; }
    try {
      await createRule({ ...form, reorder_quantity: Number(form.reorder_quantity), preferred_supplier_id: form.preferred_supplier_id || null }).unwrap();
      toast.success('Reorder rule created');
      setOpen(false);
      setForm({ business_branch_product_id: '', reorder_quantity: '', preferred_supplier_id: '', auto_approve: false });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Add Rule</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Reorder Rule</DialogTitle>
          <DialogDescription>Auto-trigger purchase orders when stock hits the reorder level.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Product</Label>
            <Select value={form.business_branch_product_id} onValueChange={(v) => setForm({ ...form, business_branch_product_id: v })}>
              <SelectTrigger><SelectValue placeholder='Select product' /></SelectTrigger>
              <SelectContent>
                {products?.map((p: any) => <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className='space-y-2'>
            <Label>Reorder Quantity</Label>
            <Input type='number' value={form.reorder_quantity} onChange={(e) => setForm({ ...form, reorder_quantity: e.target.value })} placeholder='e.g. 50' />
          </div>
          <div className='space-y-2'>
            <Label>Preferred Supplier (optional)</Label>
            <Select value={form.preferred_supplier_id} onValueChange={(v) => setForm({ ...form, preferred_supplier_id: v })}>
              <SelectTrigger><SelectValue placeholder='Select supplier' /></SelectTrigger>
              <SelectContent>
                {suppliers?.map((s: any) => <SelectItem key={s.id} value={String(s.id)}>{s.user?.firstname} {s.user?.lastname}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className='flex items-center gap-3'>
            <Switch checked={form.auto_approve} onCheckedChange={(v) => setForm({ ...form, auto_approve: v })} />
            <Label>Auto-approve purchase orders</Label>
          </div>
          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>Cancel</Button>
            <Button type='submit'>Save</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
