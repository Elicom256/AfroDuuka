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
import { Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';

interface EditPurchaseProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  purchase: any;
  products: any[];
  updatePurchase: any;
}

interface PurchaseItem {
  product_id: string;
  quantity: string;
  cost_price: string;
}

export const EditPurchase = ({ open, onOpenChange, purchase, products, updatePurchase }: EditPurchaseProps) => {
  const { currency } = useCurrency();
  const [formData, setFormData] = useState<{ items: PurchaseItem[]; supplier_id: string; note: string }>({
    items: [{ product_id: '', quantity: '', cost_price: '' }],
    supplier_id: '',
    note: '',
  });

  useEffect(() => {
    if (purchase) {
      // Support both old and new purchase structures
      const items = purchase.purchase_items || purchase.items || [];
      const mappedItems = items.map((item: any) => ({
        product_id: String(item.product_id ?? ''),
        quantity: String(item.quantity ?? ''),
        cost_price: String(item.cost_price ?? item.unit_cost ?? ''),
      }));

      setFormData({
        supplier_id: String(purchase.supplier_id ?? ''),
        items: mappedItems.length > 0 ? mappedItems : [{ product_id: '', quantity: '', cost_price: '' }],
        note: purchase.note ?? '',
      });
    }
  }, [purchase]);

  // Add new item
  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { product_id: '', quantity: '', cost_price: '' }],
    }));
  };

  // Remove item
  const removeItem = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.length > 1 ? prev.items.filter((_, i) => i !== index) : prev.items,
    }));
  };

  // Update item
  const updateItem = (index: number, field: keyof PurchaseItem, value: string) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
    }));
  };

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    const validItems = formData.items.filter((item) => item.product_id && item.quantity && item.cost_price);

    if (validItems.length === 0) {
      toast.error('Please add at least one purchase item.');
      return;
    }

    if (!formData.supplier_id) {
      toast.error('Please select a supplier.');
      return;
    }

    try {
      // Calculate subtotals for each item
      const itemsWithSubtotals = validItems.map((item) => ({
        product_id: Number(item.product_id),
        quantity: Number(item.quantity),
        cost_price: Number(item.cost_price),
        subtotal: Number(item.quantity) * Number(item.cost_price),
      }));

      // Calculate total amount
      const total_amount = itemsWithSubtotals.reduce((sum, item) => sum + item.subtotal, 0);

      const body = {
        supplier_id: Number(formData.supplier_id),
        total_amount,
        note: formData.note,
        items: itemsWithSubtotals,
      };

      const res = await updatePurchase({ id: purchase.id, body }).unwrap();
      if (res) {
        toast.success(res.message || 'Purchase updated successfully');
        onOpenChange(false);
      }
    } catch (error) {
      toast.error('Failed to update purchase');
      console.error(error);
    }
  };

  const handleChange = (field: 'supplier_id' | 'note', value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const selectedIds = formData.items.map((i) => i.product_id);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-106.25 max-h-[80vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Edit Purchase</DialogTitle>
          <DialogDescription>Update the purchase order details.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='supplier_id' className='text-right'>
                Supplier
              </Label>
              <Input
                id='supplier_id'
                type='number'
                value={formData.supplier_id}
                onChange={(e) => handleChange('supplier_id', e.target.value)}
                className='col-span-3'
                placeholder='Supplier ID'
                required
              />
            </div>

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
                    <Label htmlFor={`product_id-${index}`} className='text-right'>
                      Product
                    </Label>
                    <Select value={item.product_id} onValueChange={(value) => updateItem(index, 'product_id', value)}>
                      <SelectTrigger id={`product_id-${index}`} className='col-span-3'>
                        <SelectValue placeholder='Select product' />
                      </SelectTrigger>
                      <SelectContent>
                        {products
                          .filter(
                            (product) =>
                              !selectedIds.includes(String(product.id)) || String(product.id) === item.product_id,
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
                    <Label htmlFor={`cost_price-${index}`} className='text-right'>
                      Cost Price
                    </Label>
                    <Input
                      id={`cost_price-${index}`}
                      type='number'
                      value={item.cost_price}
                      onChange={(e) => updateItem(index, 'cost_price', e.target.value)}
                      className='col-span-3'
                      required
                    />
                  </div>
                  {item.quantity && item.cost_price && (
                    <div className='grid grid-cols-4 items-center gap-4'>
                      <p className='text-right text-sm font-medium'>Subtotal:</p>
                      <p className='col-span-3 text-sm'>
                        {currency} {(Number(item.quantity) * Number(item.cost_price)).toLocaleString()}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            ))}

            <div className='flex justify-end'>
              <Button type='button' variant='secondary' onClick={addItem}>
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
            <Button type='submit'>Update Purchase</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
