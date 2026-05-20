import React, { useState } from 'react';
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
import { Plus } from 'lucide-react';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { toast } from 'sonner';

interface AddProductProps {
  addProduct: any;
}

export const AddProduct: React.FC<AddProductProps> = ({ addProduct }) => {
  const [open, setOpen] = useState(false);
  const { data } = useProductsQuery();

  // console.log('Category==>', data);
  const [formData, setFormData] = useState({
    name: '',

    price: '',
    cost_price: '',
    quantity: '',
    reorder_level: '',
    description: '',
    product_id: '',
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    // TODO: Call addProduct mutation
    const res = await addProduct({
      ...formData,
      quantity: Number(formData.quantity),
      reorder_level: Number(formData.reorder_level),
    }).unwrap();
    console.log('Add product res==>', res);
    if (res) {
      toast.success(res.message);
    }
    console.log('Adding product:', res);
    setOpen(false);
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Add Product
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Add New Product</DialogTitle>
          <DialogDescription>Enter the details for the new product.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='name' className='text-right'>
                Name
              </Label>
              <Input
                id='name'
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            {/* <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='sku' className='text-right'>
                SKU
              </Label>
              <Input
                id='sku'
                value={formData.sku}
                onChange={(e) => handleChange('sku', e.target.value)}
                className='col-span-3'
                required
              />
            </div> */}

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='price' className='text-right'>
                Price
              </Label>
              <Input
                id='price'
                type='number'
                value={formData.price}
                onChange={(e) => handleChange('price', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='cost_price' className='text-right'>
                Cost Price
              </Label>
              <Input
                id='cost_price'
                type='number'
                value={formData.cost_price}
                onChange={(e) => handleChange('cost_price', e.target.value)}
                className='col-span-3'
                required
              />
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
              <Label htmlFor='reorder_level' className='text-right'>
                Reorder Level
              </Label>
              <Input
                id='reorder_level'
                type='number'
                value={formData.reorder_level}
                onChange={(e) => handleChange('reorder_level', e.target.value)}
                className='col-span-3'
                required
              />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='product_id' className='text-right'>
                Category
              </Label>
              <Select value={formData.product_id} onValueChange={(value) => handleChange('product_id', value)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>

                <SelectContent>
                  {data?.products.map((cat: any) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='description' className='text-right'>
                Description
              </Label>
              <Textarea
                id='description'
                value={formData.description}
                onChange={(e) => handleChange('description', e.target.value)}
                className='col-span-3'
              />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Add Product</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
