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

interface AddPurchaseProps {
  addPurchase: any;
  products: any[];
  suppliers: any[];
  paymentMethods: any[]; // ← Added for consistency
}

interface PurchaseItem {
  business_branch_product_id: string;
  quantity: string;
  cost_price: string;
}

export const AddPurchase = ({ addPurchase, products, suppliers, paymentMethods }: AddPurchaseProps) => {
  const [open, setOpen] = useState(false);

  const [formData, setFormData] = useState({
    items: [{ business_branch_product_id: '', quantity: '', cost_price: '' }] as PurchaseItem[],
    supplier_id: '',
    payment_status_id: '',
    reference: '',
    note: '',
  });

  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { business_branch_product_id: '', quantity: '', cost_price: '' }],
    }));
  };

  const removeItem = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.length > 1 ? prev.items.filter((_, i) => i !== index) : prev.items,
    }));
  };

  const updateItem = (index: number, field: keyof PurchaseItem, value: string) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
    }));
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    const validItems = formData.items.filter(
      (item) => item.business_branch_product_id && item.quantity && item.cost_price,
    );

    if (validItems.length === 0) {
      toast.error('Please add at least one purchase item.');
      return;
    }

    if (!formData.supplier_id) {
      toast.error('Please select a supplier.');
      return;
    }

    if (!formData.payment_status_id) {
      toast.error('Please select a payment method.');
      return;
    }

    try {
      const body = {
        supplier_id: Number(formData.supplier_id),
        items: validItems.map((item) => ({
          business_branch_product_id: Number(item.business_branch_product_id),
          quantity: Number(item.quantity),
          cost_price: Number(item.cost_price),
        })),
        payment_status_id: formData.payment_status_id,
        reference: formData.reference || null,
        note: formData.note,
      };
      console.log('Data to be sent==>', formData);
      const res = await addPurchase(body).unwrap();
      if (res) {
        toast.success(res.message || 'Purchase recorded successfully');
        // Reset form
        setOpen(false);
        setFormData({
          items: [{ business_branch_product_id: '', quantity: '', cost_price: '' }],
          supplier_id: '',
          payment_status_id: '',
          reference: '',
          note: '',
        });
        return res;
      }
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to create purchase');
      console.error(error);
    }
  };

  const selectedIds = formData.items.map((i) => i.business_branch_product_id);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Record Purchase
        </Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-275 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Record New Purchase</DialogTitle>
          <DialogDescription>
            Add stock from supplier. This will automatically update inventory and cash flow.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6'>
          {/* Supplier Selection */}
          <div className='grid grid-cols-4 items-center gap-4'>
            <Label htmlFor='supplier_id' className='text-right'>
              Supplier <span className='text-red-500'>*</span>
            </Label>
            <Select value={formData.supplier_id} onValueChange={(v) => handleChange('supplier_id', v)}>
              <SelectTrigger className='col-span-3'>
                <SelectValue placeholder='Select supplier' />
              </SelectTrigger>
              <SelectContent>
                {suppliers.map((supplier: any) => (
                  <SelectItem key={supplier.id} value={String(supplier.id)}>
                    {supplier.company_name || `${supplier.user.firstname} ${supplier.user.lastname}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Items Section */}
          <div className='space-y-4'>
            <div className='flex justify-between items-center'>
              <h3 className='font-medium'>Purchase Items</h3>
              <Button type='button' variant='secondary' onClick={addItem}>
                <Plus className='h-4 w-4 mr-2' />
                Add Item
              </Button>
            </div>

            {formData.items.map((item, index) => (
              <div key={index} className='rounded-lg border p-4 '>
                <div className='flex justify-between mb-3'>
                  <p className='font-medium text-sm'>Item {index + 1}</p>
                  <Button
                    variant='ghost'
                    size='sm'
                    type='button'
                    onClick={() => removeItem(index)}
                    disabled={formData.items.length === 1}
                  >
                    <Trash2 className='h-4 w-4' />
                  </Button>
                </div>

                <div className='grid grid-cols-12 gap-4'>
                  <div className='col-span-5'>
                    <Label>Product</Label>
                    <Select
                      value={item.business_branch_product_id}
                      onValueChange={(value) => updateItem(index, 'business_branch_product_id', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder='Select product' />
                      </SelectTrigger>
                      <SelectContent>
                        {products
                          .filter(
                            (p) =>
                              !selectedIds.includes(String(p.id)) || String(p.id) === item.business_branch_product_id,
                          )
                          .map((product) => (
                            <SelectItem key={product.id} value={String(product.id)}>
                              {product.name}
                            </SelectItem>
                          ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className='col-span-3'>
                    <Label>Quantity</Label>
                    <Input
                      type='number'
                      value={item.quantity}
                      onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      required
                    />
                  </div>

                  <div className='col-span-4'>
                    <Label>Cost Price (UGX)</Label>
                    <Input
                      type='number'
                      value={item.cost_price}
                      onChange={(e) => updateItem(index, 'cost_price', e.target.value)}
                      required
                    />
                  </div>

                  {item.quantity && item.cost_price && (
                    <div className='col-span-12 text-sm text-right font-medium'>
                      Subtotal: UGX {(Number(item.quantity) * Number(item.cost_price)).toLocaleString()}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>

          {/* Payment & Extra Info */}
          <div className='grid grid-cols-2 gap-6'>
            <div>
              <Label>Payment Method</Label>
              <Select value={formData.payment_status_id} onValueChange={(v) => handleChange('payment_status_id', v)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select payment method' />
                </SelectTrigger>
                <SelectContent>
                  {paymentMethods?.map((pm: any) => (
                    <SelectItem key={pm.id} value={String(pm.id)}>
                      {pm.method}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Reference / Invoice No.</Label>
              <Input
                value={formData.reference}
                onChange={(e) => handleChange('reference', e.target.value)}
                placeholder='Supplier invoice number'
              />
            </div>
          </div>

          <div>
            <Label>Note</Label>
            <Textarea
              value={formData.note}
              onChange={(e) => handleChange('note', e.target.value)}
              placeholder='Additional notes about this purchase...'
            />
          </div>

          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type='submit'>Save Purchase</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
