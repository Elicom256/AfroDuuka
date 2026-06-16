import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

const types = ['points', 'stamps', 'tiered'];

export const AddLoyaltyProgram = ({ createProgram }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: '', type: 'points', points_per_currency: '1', redemption_rate: '1', expiry_days: '' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name) { toast.error('Program name required'); return; }
    try {
      await createProgram({
        ...form,
        points_per_currency: Number(form.points_per_currency),
        redemption_rate: Number(form.redemption_rate),
        expiry_days: form.expiry_days ? Number(form.expiry_days) : null,
      }).unwrap();
      toast.success('Program created');
      setOpen(false);
      setForm({ name: '', type: 'points', points_per_currency: '1', redemption_rate: '1', expiry_days: '' });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> New Program</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Loyalty Program</DialogTitle>
          <DialogDescription>Define how customers earn and redeem points.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Name</Label>
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder='e.g. Silver Rewards' />
          </div>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Type</Label>
              <Select value={form.type} onValueChange={(v) => setForm({ ...form, type: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {types.map((t) => <SelectItem key={t} value={t}>{t}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-2'>
              <Label>Expiry (days)</Label>
              <Input type='number' value={form.expiry_days} onChange={(e) => setForm({ ...form, expiry_days: e.target.value })} placeholder='365' />
            </div>
          </div>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Points per currency</Label>
              <Input type='number' step='0.01' value={form.points_per_currency} onChange={(e) => setForm({ ...form, points_per_currency: e.target.value })} />
            </div>
            <div className='space-y-2'>
              <Label>Redemption rate</Label>
              <Input type='number' step='0.01' value={form.redemption_rate} onChange={(e) => setForm({ ...form, redemption_rate: e.target.value })} />
            </div>
          </div>
          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>Cancel</Button>
            <Button type='submit'>Create</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
