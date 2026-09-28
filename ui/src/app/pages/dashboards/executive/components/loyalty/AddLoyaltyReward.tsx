import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export const AddLoyaltyReward = ({ createReward }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: '', description: '', points_required: '', stock: '0' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.points_required) { toast.error('Name and points required'); return; }
    try {
      await createReward({ ...form, points_required: Number(form.points_required), stock: Number(form.stock) }).unwrap();
      toast.success('Reward created');
      setOpen(false);
      setForm({ name: '', description: '', points_required: '', stock: '0' });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Add Reward</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Loyalty Reward</DialogTitle>
          <DialogDescription>Set a reward that customers can redeem with points.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Name</Label>
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder='e.g. Free Soda' />
          </div>
          <div className='space-y-2'>
            <Label>Description</Label>
            <Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} placeholder='Describe the reward' />
          </div>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Points Required</Label>
              <Input type='number' value={form.points_required} onChange={(e) => setForm({ ...form, points_required: e.target.value })} />
            </div>
            <div className='space-y-2'>
              <Label>Stock</Label>
              <Input type='number' value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} />
            </div>
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
