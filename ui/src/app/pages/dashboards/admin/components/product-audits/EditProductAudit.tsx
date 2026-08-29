import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Package } from 'lucide-react';
import { toast } from 'sonner';
import { useUpdateProductAuditMutation } from '@/app/store/features/audit/productAuditQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  audit: any;
};

export const EditProductAudit = ({ open, onOpenChange, audit }: Props) => {
  const [updateAudit, { isLoading }] = useUpdateProductAuditMutation();
  const { data: productsData } = useProductsQuery();
  const products = productsData?.products?.data ?? [];

  const [formData, setFormData] = useState({
    audit_date: '',
    status: 'draft',
    notes: '',
  });
  const [items, setItems] = useState<Array<{ product_id: string; counted_quantity: string; notes: string }>>([]);

  useEffect(() => {
    if (audit) {
      setFormData({
        audit_date: audit.audit_date?.split('T')[0] || '',
        status: audit.status || 'draft',
        notes: audit.notes || '',
      });
      setItems(
        (audit.items || []).map((item: any) => ({
          product_id: String(item.product_id),
          counted_quantity: String(item.counted_quantity),
          notes: item.notes || '',
        }))
      );
    }
  }, [audit]);

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
    if (!audit) return;
    if (items.some((item) => !item.product_id)) {
      toast.error('Please select a product for all items');
      return;
    }
    try {
      await updateAudit({
        id: audit.id,
        body: {
          ...formData,
          items: items.map((item) => ({
            product_id: Number(item.product_id),
            counted_quantity: Number(item.counted_quantity),
            notes: item.notes || null,
          })),
        },
      }).unwrap();
      toast.success('Product audit updated');
      onOpenChange(false);
    } catch {
      toast.error('Failed to update product audit');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-2xl max-h-[80vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Edit Product Audit</DialogTitle>
          <DialogDescription>Update audit details for {audit?.audit_number}.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Audit Date</Label>
              <Input
                type='date'
                value={formData.audit_date}
                onChange={(e) => handleChange('audit_date', e.target.value)}
                className='col-span-3'
              />
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
                            {p.name} ({p.sku})
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className='col-span-3'>
                    <Input
                      type='number' min='0' placeholder='Counted qty'
                      value={item.counted_quantity}
                      onChange={(e) => handleItemChange(index, 'counted_quantity', e.target.value)}
                    />
                  </div>
                  <div className='col-span-3'>
                    <Input placeholder='Notes' value={item.notes} onChange={(e) => handleItemChange(index, 'notes', e.target.value)} />
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
            <Button type='submit' disabled={isLoading}>{isLoading ? 'Updating...' : 'Update Audit'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
