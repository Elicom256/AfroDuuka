import { useState, type SyntheticEvent } from 'react';
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

interface AddSaleProps {
  addSale: any;
  products: any[];
}

interface SaleItem {
  business_branch_product_id: string;
  quantity: string;
  unit_price: string;
}

export const AddSale = ({ addSale, products }: AddSaleProps) => {
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState<{ items: SaleItem[]; note: string }>({
    items: [{ business_branch_product_id: '', quantity: '', unit_price: '' }],
    note: '',
  });
  // user can add an item on the list
  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { business_branch_product_id: '', quantity: '', unit_price: '' }],
    }));
  };
  // user can remove an item on the list
  const removeItem = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.length > 1 ? prev.items.filter((_, i) => i !== index) : prev.items,
    }));
  };
  // user can update item in the list
  const updateItem = (index: number, field: keyof SaleItem, value: string) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
    }));
  };

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    const validItems = formData.items.filter(
      (item) => item.business_branch_product_id && item.quantity && item.unit_price,
    );

    if (validItems.length === 0) {
      toast.error('Please add at least one sale item.');
      return;
    }

    try {
      const body = {
        items: validItems.map((item) => ({
          business_branch_product_id: Number(item.business_branch_product_id),
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
        })),
        note: formData.note,
      };

      const res = await addSale(body).unwrap();
      if (res) {
        console.log('created Sale==>', res);
        toast.success(res.message || 'Sale created successfully');
        setOpen(false);
        setFormData({ items: [{ business_branch_product_id: '', quantity: '', unit_price: '' }], note: '' });
      }
    } catch (error) {
      toast.error('Failed to create sale');
      console.error(error);
    }
  };

  const handleChange = (field: 'note', value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const selectedIds = formData.items.map((i) => i.business_branch_product_id);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Add Sale
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25 max-h-[80vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Record a Sale</DialogTitle>
          <DialogDescription>Add a new sales record for a product.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            {formData.items.map((item, index) => (
              <div key={index} className='rounded-lg border p-4'>
                <div className='flex items-center justify-between gap-4'>
                  <p className='text-sm font-medium'>Item {index + 1}</p>
                  <Button
                    variant='ghost'
                    type='button'
                    onClick={() => removeItem(index)}
                    disabled={formData.items.length === 1}
                    className='h-9 w-9 p-0'
                  >
                    <Trash2 className='h-4 w-4' />
                  </Button>
                </div>
                <div className='grid gap-4 pt-4'>
                  <div className='grid grid-cols-4 items-center gap-4'>
                    <Label htmlFor={`business_branch_product_id-${index}`} className='text-right'>
                      Product
                    </Label>
                    <Select
                      value={item.business_branch_product_id}
                      onValueChange={(value) => updateItem(index, 'business_branch_product_id', value)}
                    >
                      <SelectTrigger id={`business_branch_product_id-${index}`} className='col-span-3'>
                        <SelectValue placeholder='Select product' />
                      </SelectTrigger>
                      <SelectContent>
                        {products
                          .filter(
                            (product) =>
                              !selectedIds.includes(String(product.id)) ||
                              String(product.id) === item.business_branch_product_id,
                          )
                          .map((product) => (
                            <SelectItem key={product.id} value={String(product.id)}>
                              {product.name}
                            </SelectItem>
                          ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className='grid grid-cols-4 items-center gap-4'>
                    <Label htmlFor={`quantity-${index}`} className='text-right'>
                      Quantity
                    </Label>
                    <Input
                      id={`quantity-${index}`}
                      type='number'
                      value={item.quantity}
                      onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      className='col-span-3'
                      required
                    />
                  </div>
                  <div className='grid grid-cols-4 items-center gap-4'>
                    <Label htmlFor={`unit_price-${index}`} className='text-right'>
                      Unit Price
                    </Label>
                    <Input
                      id={`unit_price-${index}`}
                      type='number'
                      value={item.unit_price}
                      onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                      className='col-span-3'
                      required
                    />
                  </div>
                </div>
              </div>
            ))}
            <div className='flex justify-end'>
              <Button type='button' variant='secondary' onClick={addItem}>
                <Plus className='h-4 w-4 mr-2' />
                Add item
              </Button>
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
            <Button type='submit'>Save Sale</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
