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
  addSale: any;
  products: any[];
  customers?: any[];
  paymentMethods?: any[];
}

interface SaleItem {
  product_id: string;
  quantity: string;
  unit_price: string;
}

/**
 * Sentinel for the "no customer" option. Radix Select reserves the empty string
 * for "clear", and sending the human label as the id made the API reject a
 * walk-in sale (`nullable|exists:customers,id`).
 */
const WALK_IN_CUSTOMER = '__walk_in__';

export const AddSale = ({ addSale, products, customers, paymentMethods }: AddSaleProps) => {
  const { currency } = useCurrency();
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState({
    items: [{ product_id: '', quantity: '', unit_price: '' }] as SaleItem[],
    customer_id: '',
    payment_status_id: '',
    paymentStatus: 'paid',
    note: '',
    reference: '',
  });

  const addItem = () => {
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, { product_id: '', quantity: '', unit_price: '' }],
    }));
  };

  const removeItem = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.length > 1 ? prev.items.filter((_, i) => i !== index) : prev.items,
    }));
  };

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

    const validItems = formData.items.filter((item) => item.product_id && item.quantity && item.unit_price);

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
        customer_id:
          formData.customer_id && formData.customer_id !== WALK_IN_CUSTOMER
            ? Number(formData.customer_id)
            : null,
        payment_status_id: formData.payment_status_id,
        paymentStatus: formData.paymentStatus,
        note: formData.note,
        reference: formData.reference || null,
      };

      const res = await addSale(body).unwrap();
      toast.success(res.message || 'Sale created successfully');
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
          Add Sale
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Record a Sale</DialogTitle>
          <DialogDescription>Add a new sales record for a product.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='customer_id' className='text-right'>
                Customer <span className='text-muted-foreground'>(optional)</span>
              </Label>
              <Select value={formData.customer_id} onValueChange={(v) => handleChange('customer_id', v)}>
                <SelectTrigger id='customer_id' className='col-span-3'>
                  <SelectValue placeholder='Select customer (optional - walk-in)' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={WALK_IN_CUSTOMER}>Walk-in Customer (No record)</SelectItem>
                  {customers?.map((cust: any) => (
                    <SelectItem key={cust.id} value={String(cust.id)}>
                      {cust.user?.firstname} {cust.user?.lastname}{' '}
                      {cust.company_name ? `(${cust.company_name})` : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
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
                                  unit_price: selected?.selling_price?.toString() || '',
                                }
                              : i,
                          ),
                        }));
                      }}
                    >
                      <SelectTrigger id={`product_id-${index}`} className='col-span-3'>
                        <SelectValue placeholder='Select product' />
                      </SelectTrigger>
                      <SelectContent>
                        {products
                          .filter(
                            (product) =>
                              !selectedIds.includes(String(product.id)) ||
                              String(product.id) === item.product_id,
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
                      Unit Price ({currency})
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
              <Label htmlFor='payment_status_id' className='text-right'>
                Payment Method
              </Label>
              <Select
                value={formData.payment_status_id}
                onValueChange={(v) => handleChange('payment_status_id', v)}
              >
                <SelectTrigger id='payment_status_id' className='col-span-3'>
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

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='reference' className='text-right'>
                Reference
              </Label>
              <Input
                id='reference'
                value={formData.reference}
                onChange={(e) => handleChange('reference', e.target.value)}
                className='col-span-3'
                placeholder='Optional receipt or transaction ID'
              />
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
