import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

const types = ['bluetooth', 'usb', 'network'];

export const AddPrinter = ({ createPrinter, branches }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: '', type: '', ip_address: '', port: '', business_branch_id: '', is_default: false });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.type) { toast.error('Name and type are required'); return; }
    try {
      await createPrinter({ ...form, port: form.port ? Number(form.port) : null }).unwrap();
      toast.success('Printer added');
      setOpen(false);
      setForm({ name: '', type: '', ip_address: '', port: '', business_branch_id: '', is_default: false });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Add Printer</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Printer</DialogTitle>
          <DialogDescription>Configure a thermal receipt printer for a branch.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Name</Label>
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder='e.g. Front Desk Printer' />
          </div>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Type</Label>
              <Select value={form.type} onValueChange={(v) => setForm({ ...form, type: v })}>
                <SelectTrigger><SelectValue placeholder='Type' /></SelectTrigger>
                <SelectContent>
                  {types.map((t) => <SelectItem key={t} value={t}>{t}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-2'>
              <Label>Branch</Label>
              <Select value={form.business_branch_id} onValueChange={(v) => setForm({ ...form, business_branch_id: v })}>
                <SelectTrigger><SelectValue placeholder='Branch' /></SelectTrigger>
                <SelectContent>
                  {branches?.map((b: any) => <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          {form.type === 'network' && (
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>IP Address</Label>
                <Input value={form.ip_address} onChange={(e) => setForm({ ...form, ip_address: e.target.value })} placeholder='192.168.1.100' />
              </div>
              <div className='space-y-2'>
                <Label>Port</Label>
                <Input type='number' value={form.port} onChange={(e) => setForm({ ...form, port: e.target.value })} placeholder='9100' />
              </div>
            </div>
          )}
          <div className='flex items-center gap-3'>
            <Switch checked={form.is_default} onCheckedChange={(v) => setForm({ ...form, is_default: v })} />
            <Label>Set as default printer</Label>
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
