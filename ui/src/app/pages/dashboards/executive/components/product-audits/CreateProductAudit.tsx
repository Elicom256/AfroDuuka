import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Package } from 'lucide-react';
import { toast } from 'sonner';
import { useAddProductAuditMutation } from '@/app/store/features/audit/productAuditQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';

type Props = {
  branches: any[];
};

export const CreateProductAudit = ({ branches }: Props) => {
  const [open, setOpen] = useState(false);
  const [addAudit, { isLoading }] = useAddProductAuditMutation();
  const { data: productsData } = useProductsQuery();

  const products = productsData?.products?.data ?? [];
  const [formData, setFormData] = useState({
    business_branch_id: '',
    audit_date: new Date().toISOString().split('T')[0],
    status: 'draft',
    notes: '',
  });
  const [items, setItems] = useState<Array<{ product_id: string; counted_quantity: string; notes: string }>>([
    { product_id: '', counted_quantity: '0', notes: '' },
  ]);

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleItemChange = (index: number, field: string, value: string) => {
    setItems((prev) => prev.map((item, i) => (i === index ? { ...item, [field]: value } : item)));
  };

  const addItem = () => {
    setItems((prev) => [...prev, { product_id: '', counted_quantity: '0', notes: '' }]);
  };

  const removeItem = (index: number) => {
    setItems((prev) => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!formData.business_branch_id) {
      
      toast.error('Please select a branch');
      return;
    }
    if (items.some((item) => !item.product_id)) {
      toast.error('Please select a product for all items');
      return;
    }
    try {
      await addAudit({
        ...formData,
        items: items.map((item) => ({
          product_id: Number(item.product_id),
          counted_quantity: Number(item.counted_quantity),
          notes: item.notes || null,
        })),
      }).unwrap();
      toast.success('Product audit created');
      setOpen(false);
      setFormData({ business_branch_id: '', audit_date: new Date().toISOString().split('T')[0], status: 'draft', notes: '' });
      setItems([{ product_id: '', counted_quantity: '0', notes: '' }]);
    } catch {
      toast.error('Failed to create product audit');
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button className='rounded-2xl'><Plus className='mr-2 h-4 w-4' /> New Product Audit</Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-2xl max-h-[80vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Create Product Audit</DialogTitle>
          <DialogDescription>Record a physical stock count for a branch.</DialogDescription>
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
              <Label className='text-right'>Audit Date</Label>
              <Input
                type='date'
                value={formData.audit_date}
                onChange={(e) => handleChange('audit_date', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Status</Label>
              <Select value={formData.status} onValueChange={(v) => handleChange('status', v)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select status' />
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
              <Textarea
                value={formData.notes}
                onChange={(e) => handleChange('notes', e.target.value)}
                className='col-span-3'
                rows={2}
              />
            </div>

            <div className='border-t pt-4 mt-2'>
              <div className='flex items-center justify-between mb-4'>
                <h4 className='text-sm font-semibold flex items-center gap-2'><Package className='h-4 w-4' /> Audit Items</h4>
                <Button type='button' variant='outline' size='sm' onClick={addItem} className='rounded-2xl'>
                  <Plus className='h-3 w-3 mr-1' /> Add Product
                </Button>
              </div>
              {items.map((item, index) => (
                <div key={index} className='grid grid-cols-12 gap-2 mb-2 items-end'>
                  <div className='col-span-5'>
                    <Select value={item.product_id} onValueChange={(v) => handleItemChange(index, 'product_id', v)}>
                      <SelectTrigger><SelectValue placeholder='Product' /></SelectTrigger>
                      <SelectContent>
                        {products.map((p: any) => (
                          <SelectItem key={p.id} value={String(p.id)}>
                            {p.name} ({p.sku}) - Qty: {p.quantity}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className='col-span-3'>
                    <Input
                      type='number'
                      min='0'
                      placeholder='Counted qty'
                      value={item.counted_quantity}
                      onChange={(e) => handleItemChange(index, 'counted_quantity', e.target.value)}
                    />
                  </div>
                  <div className='col-span-3'>
                    <Input
                      placeholder='Notes'
                      value={item.notes}
                      onChange={(e) => handleItemChange(index, 'notes', e.target.value)}
                    />
                  </div>
                  <div className='col-span-1'>
                    {items.length > 1 && (
                      <Button type='button' variant='ghost' size='icon' onClick={() => removeItem(index)}>
                        <Trash2 className='h-4 w-4 text-red-500' />
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
          <DialogFooter>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? 'Creating...' : 'Create Audit'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
