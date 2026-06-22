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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export const AddStockTransfer = ({ createTransfer, branches, products }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({
    from_branch_id: '',
    to_branch_id: '',
    notes: '',
    items: [{ business_branch_product_id: '', quantity_expected: '' }],
  });
  // console.log('branches==>', branches);
  // console.log('products==>', products);

  const addItem = () =>
    setForm((p) => ({ ...p, items: [...p.items, { business_branch_product_id: '', quantity_expected: '' }] }));
  const removeItem = (i: number) => setForm((p) => ({ ...p, items: p.items.filter((_, idx) => idx !== i) }));
  const updateItem = (i: number, field: string, value: string) =>
    setForm((p) => ({ ...p, items: p.items.map((item, idx) => (idx === i ? { ...item, [field]: value } : item)) }));

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    const validItems = form.items.filter((i) => i.business_branch_product_id && i.quantity_expected);
    if (!form.from_branch_id || !form.to_branch_id) {
      toast.error('Select source and destination branches');
      return;
    }
    if (form.from_branch_id === form.to_branch_id) {
      toast.error('Source and destination must differ');
      return;
    }
    if (!validItems.length) {
      toast.error('Add at least one item');
      return;
    }
    try {
      const res = await createTransfer({
        ...form,
        items: validItems.map((i) => ({ ...i, quantity_expected: Number(i.quantity_expected) })),
      }).unwrap();

      toast.success(res.message ?? 'Stock transfer created');
      setOpen(false);
      setForm({
        from_branch_id: '',
        to_branch_id: '',
        notes: '',
        items: [{ business_branch_product_id: '', quantity_expected: '' }],
      });
    } catch (err: any) {
      console.log('error==>', err);
      toast.error(err?.data?.message || 'Failed');
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' /> New Transfer
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-2xl max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Create Stock Transfer</DialogTitle>
          <DialogDescription>
            Move stock between branches. Items will be deducted from source on dispatch.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-6'>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>From Branch</Label>
              <Select value={form.from_branch_id} onValueChange={(v) => setForm((p) => ({ ...p, from_branch_id: v }))}>
                <SelectTrigger>
                  <SelectValue placeholder='Source branch' />
                </SelectTrigger>
                <SelectContent>
                  {branches?.map((b: any) => (
                    <SelectItem key={b.id} value={String(b.id)}>
                      {b.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-2'>
              <Label>To Branch</Label>
              <Select value={form.to_branch_id} onValueChange={(v) => setForm((p) => ({ ...p, to_branch_id: v }))}>
                <SelectTrigger>
                  <SelectValue placeholder='Destination branch' />
                </SelectTrigger>
                <SelectContent>
                  {branches?.map((b: any) => (
                    <SelectItem key={b.id} value={String(b.id)}>
                      {b.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className='space-y-3'>
            <div className='flex justify-between items-center'>
              <span className='font-medium'>Items</span>
              <Button type='button' variant='outline' size='sm' onClick={addItem}>
                <Plus className='h-3 w-3 mr-1' /> Add Item
              </Button>
            </div>
            {form.items.map((item, idx) => (
              <div key={idx} className='flex gap-3 items-end p-3 bg-muted/50 rounded-lg'>
                <div className='flex-1 space-y-1'>
                  <Label className='text-xs'>Product</Label>
                  <Select
                    value={item.business_branch_product_id}
                    onValueChange={(v) => updateItem(idx, 'business_branch_product_id', v)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder='Product' />
                    </SelectTrigger>
                    <SelectContent>
                      {products?.map((p: any) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                          {p.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className='w-24 space-y-1'>
                  <Label className='text-xs'>Qty</Label>
                  <Input
                    type='number'
                    value={item.quantity_expected}
                    onChange={(e) => updateItem(idx, 'quantity_expected', e.target.value)}
                  />
                </div>
                <Button type='button' variant='ghost' size='icon' onClick={() => removeItem(idx)}>
                  <Trash2 className='h-4 w-4 text-destructive' />
                </Button>
              </div>
            ))}
          </div>

          <div className='space-y-2'>
            <Label>Notes</Label>
            <Textarea
              value={form.notes}
              onChange={(e) => setForm((p) => ({ ...p, notes: e.target.value }))}
              placeholder='Optional notes'
            />
          </div>

          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type='submit'>Create Transfer</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
