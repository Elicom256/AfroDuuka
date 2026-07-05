import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export const AddPaymentGateway = ({ createGateway }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ provider: '', is_active: true });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.provider.trim()) { toast.error('Provider name is required'); return; }
    try {
      await createGateway(form).unwrap();
      toast.success('Gateway added');
      setOpen(false);
      setForm({ provider: '', is_active: true });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Add Gateway</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Payment Gateway</DialogTitle>
          <DialogDescription>Enter a payment provider name (e.g. mtn_momo, airtel_money, flutterwave, pesapal).</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Provider</Label>
            <Input
              value={form.provider}
              onChange={(e) => setForm({ ...form, provider: e.target.value })}
              placeholder='e.g. mtn_momo, airtel_money, flutterwave'
            />
          </div>
          <div className='flex items-center gap-3'>
            <Switch checked={form.is_active} onCheckedChange={(v) => setForm({ ...form, is_active: v })} />
            <Label>Active</Label>
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
