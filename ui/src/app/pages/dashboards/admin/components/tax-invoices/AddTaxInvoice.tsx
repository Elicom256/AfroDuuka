import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export const AddTaxInvoice = ({ createInvoice, sales }: any) => {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ sale_id: '', invoice_number: '', vat_amount: '', total_amount: '' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.sale_id || !form.invoice_number) { toast.error('Sale and invoice number required'); return; }
    try {
      await createInvoice({ ...form, vat_amount: Number(form.vat_amount), total_amount: Number(form.total_amount) }).unwrap();
      toast.success('Tax invoice created');
      setOpen(false);
      setForm({ sale_id: '', invoice_number: '', vat_amount: '', total_amount: '' });
    } catch (err: any) { toast.error(err?.data?.message || 'Failed'); }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild><Button size='sm'><Plus className='h-4 w-4 mr-2' /> Create Invoice</Button></DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Tax Invoice</DialogTitle>
          <DialogDescription>Generate a URA-compliant e-invoice for a sale.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='space-y-2'>
            <Label>Sale</Label>
            <Select value={form.sale_id} onValueChange={(v) => {
              const sale = sales?.find((s: any) => String(s.id) === v);
              setForm({ ...form, sale_id: v, total_amount: sale?.total_amount || '' });
            }}>
              <SelectTrigger><SelectValue placeholder='Select sale' /></SelectTrigger>
              <SelectContent>
                {sales?.map((s: any) => <SelectItem key={s.id} value={String(s.id)}>Sale #{s.id} — UGX {Number(s.total_amount).toLocaleString()}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className='space-y-2'>
            <Label>Invoice Number</Label>
            <Input value={form.invoice_number} onChange={(e) => setForm({ ...form, invoice_number: e.target.value })} placeholder='e.g. INV-2024-001' />
          </div>
          <div className='grid grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Total Amount (UGX)</Label>
              <Input type='number' value={form.total_amount} onChange={(e) => setForm({ ...form, total_amount: e.target.value })} />
            </div>
            <div className='space-y-2'>
              <Label>VAT Amount (UGX)</Label>
              <Input type='number' value={form.vat_amount} onChange={(e) => setForm({ ...form, vat_amount: e.target.value })} />
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
