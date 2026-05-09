import { useEffect, useState, type SyntheticEvent } from 'react';
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
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';

interface EditSaleProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  sale: any;
  products: any[];
  updateOrder: any;
}

export const EditSale = ({ open, onOpenChange, sale, products, updateOrder }: EditSaleProps) => {
  const [formData, setFormData] = useState({
    product_id: '',
    quantity: '',
    unit_price: '',
    status: '',
    note: '',
  });

  useEffect(() => {
    if (sale) {
      setFormData({
        product_id: String(sale.product_id ?? sale.product?.id ?? ''),
        quantity: String(sale.quantity ?? ''),
        unit_price: String(sale.unit_price ?? sale.price ?? ''),
        status: sale.status ?? 'completed',
        note: sale.note ?? '',
      });
    }
  }, [sale]);

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const body = {
        product_id: Number(formData.product_id),
        quantity: Number(formData.quantity),
        unit_price: Number(formData.unit_price),
        status: formData.status,
        note: formData.note,
      };
      const res = await updateOrder({ id: sale.id, body }).unwrap();
      if (res) {
        toast.success(res.message || 'Sale updated successfully');
        onOpenChange(false);
      }
    } catch (error) {
      toast.error('Failed to update sale');
      console.error(error);
    }
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Edit Sale</DialogTitle>
          <DialogDescription>Update details for the selected sale.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='product_id' className='text-right'>
                Product
              </Label>
              <Select value={formData.product_id} onValueChange={(value) => handleChange('product_id', value)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select product' />
                </SelectTrigger>
                <SelectContent>
                  {products.map((product) => (
                    <SelectItem key={product.id} value={String(product.id)}>
                      {product.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='quantity' className='text-right'>
                Quantity
              </Label>
              <Input
                id='quantity'
                type='number'
                value={formData.quantity}
                onChange={(e) => handleChange('quantity', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='unit_price' className='text-right'>
                Unit Price
              </Label>
              <Input
                id='unit_price'
                type='number'
                value={formData.unit_price}
                onChange={(e) => handleChange('unit_price', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='status' className='text-right'>
                Status
              </Label>
              <Select value={formData.status} onValueChange={(value) => handleChange('status', value)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Sale status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='completed'>Completed</SelectItem>
                  <SelectItem value='pending'>Pending</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='note' className='text-right'>
                Note
              </Label>
              <Textarea
                id='note'
                value={formData.note}
                onChange={(e) => handleChange('note', e.target.value)}
                className='col-span-3'
              />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Update Sale</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
