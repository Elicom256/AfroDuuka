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
import { useCurrency } from '@/app/hooks/useCurrency';

interface AddSaleProps {
  addSale: any; // RTK Query mutation
  products: any[]; // Product list
  customers: any[]; // ← New: Pass customers
  paymentMethods: any[]; // ← Already have this
}

interface SaleItem {
  product_id: string;
  quantity: string;
  unit_price: string;
}

export const AddSale = ({ addSale, products, customers, paymentMethods }: AddSaleProps) => {
  const { currency } = useCurrency();
  const [open, setOpen] = useState(false);

  const [formData, setFormData] = useState({
    items: [{ product_id: '', quantity: '', unit_price: '' }] as SaleItem[],
    customer_id: '', // nullable
    payment_status_id: '', // payment method
    paymentStatus: 'paid', // default
    note: '',
    reference: '', // optional receipt/cheque number
  });

  // Add new item row
  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { product_id: '', quantity: '', unit_price: '' }],
    }));
  };

  // Remove item row
  const removeItem = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.length > 1 ? prev.items.filter((_, i) => i !== index) : prev.items,
    }));
  };

  // Update item field
  const updateItem = (index: number, field: keyof SaleItem, value: string) => {
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
      (item) => item.product_id && item.quantity && item.unit_price,
    );

    if (validItems.length === 0) {
      toast.error('Please add at least one sale item.');
      return;
    }

    if (!formData.payment_status_id) {
      toast.error('Please select a payment method.');
      return;
    }

    try {
      const body = {
        items: validItems.map((item) => ({
          product_id: Number(item.product_id),
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
        })),
        customer_id: formData.customer_id || null,
        payment_status_id: formData.payment_status_id,
        paymentStatus: formData.paymentStatus,
        note: formData.note,
        reference: formData.reference || null,
      };

      const res = await addSale(body).unwrap();

      toast.success(res.message || 'Sale recorded successfully!');

      // Reset form
      setOpen(false);
      setFormData({
        items: [{ product_id: '', quantity: '', unit_price: '' }],
        customer_id: '',
        payment_status_id: '',
        paymentStatus: 'paid',
        note: '',
        reference: '',
      });
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to create sale');
      console.error(error);
    }
  };

  const selectedIds = formData.items.map((i) => i.product_id);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Record Sale
        </Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-275 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Record New Sale</DialogTitle>
          <DialogDescription>
            Enter sale details. Customer and payment method are required for proper tracking.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6'>
          {/* Customer Selection */}
          <div className='grid grid-cols-4 items-center gap-4'>
            <Label htmlFor='customer_id' className='text-right'>
              Customer <span className='text-red-500'>*</span>
            </Label>
            <Select value={formData.customer_id} onValueChange={(v) => handleChange('customer_id', v)}>
              <SelectTrigger className='col-span-3'>
                <SelectValue placeholder='Select customer (optional - walk-in)' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='Walk-in Customer (No record)'>Walk-in Customer (No record)</SelectItem>
                {customers?.map((cust: any) => (
                  <SelectItem key={cust.id} value={String(cust.id)}>
                    {cust.user.firstname} {cust.user.lastname} {cust.company_name ? `(${cust.company_name})` : ''}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Items Section */}
          <div className='space-y-4'>
            <div className='flex justify-between items-center'>
              <h3 className='font-medium'>Sale Items</h3>
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
                  {/* Product */}
                  <div className='col-span-5'>
                    <Label>Product</Label>
                    <Select
                      value={item.product_id}
                      onValueChange={(value) => {
                        const selected = products.find((p) => String(p.id) === value);
                        setFormData((prev) => ({
                          ...prev,
                          items: prev.items.map((i, idx) =>
                            idx === index
                              ? {
                                  ...i,
                                  product_id: value,
                                  unit_price: selected?.price?.toString() || '',
                                }
                              : i,
                          ),
                        }));
                      }}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder='Select product' />
                      </SelectTrigger>
                      <SelectContent>
                        {products
                          .filter(
                            (p) =>
                              !selectedIds.includes(String(p.id)) || String(p.id) === item.product_id,
                          )
                          .map((product) => (
                            <SelectItem key={product.id} value={String(product.id)}>
                              {product.name}
                            </SelectItem>
                          ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {/* Quantity */}
                  <div className='col-span-3'>
                    <Label>Quantity</Label>
                    <Input
                      type='number'
                      value={item.quantity}
                      onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      required
                    />
                  </div>

                  {/* Unit Price */}
                  <div className='col-span-4'>
                    <Label>Unit Price ({currency})</Label>
                    <Input
                      type='number'
                      value={item.unit_price}
                      onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                      required
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Payment & Extra Info */}
          <div className='grid grid-cols-2 gap-6'>
            <div className='w-fulll'>
              <Label>Payment Method</Label>
              <Select value={formData.payment_status_id} onValueChange={(v) => handleChange('payment_status_id', v)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select payment method' />
                </SelectTrigger>
                <SelectContent>
                  {paymentMethods?.map((pm: any) => (
                    <SelectItem key={pm.id || pm} value={String(pm.id)}>
                      {pm.method}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Reference / Receipt No.</Label>
              <Input
                value={formData.reference}
                onChange={(e) => handleChange('reference', e.target.value)}
                placeholder='Optional receipt or transaction ID'
              />
            </div>
          </div>

          <div className='grid gap-1'>
            <Label>Note</Label>
            <Textarea
              value={formData.note}
              onChange={(e) => handleChange('note', e.target.value)}
              placeholder='Any additional notes...'
            />
          </div>

          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type='submit'>Save Sale</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
