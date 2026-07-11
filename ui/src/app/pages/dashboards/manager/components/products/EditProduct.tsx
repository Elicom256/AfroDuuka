import React, { useState } from 'react';
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
import { useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { toast } from 'sonner';
import { useUpdateProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { LoadingState } from '@/utils/LoadingState';

interface EditProductProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  product: any;
  // updateProduct: any;
}

export const EditProduct: React.FC<EditProductProps> = ({ open, onOpenChange, product }) => {
  const { data: categories } = useProductCategoriesQuery();
  const [updateProduct, { isLoading }] = useUpdateProductMutation();

  const [formData, setFormData] = useState({
    name: '',

    price: '',
    cost_price: '',
    quantity: '',
    minimum_stock: '',
    status: '',
    description: '',
    product_category_id: '',
  });

  React.useEffect(() => {
    if (product) {
      setFormData({
        name: product.name || '',

        price: product.price?.toString() || '',
        cost_price: product.cost_price?.toString() || '',
        quantity: product.quantity?.toString() || '',
        minimum_stock: product.minimum_stock?.toString() || '',
        status: product.status || 'active',
        description: product.description || '',
        product_category_id: product.product_category_id?.toString() || '',
      });
    }
  }, [product]);

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await updateProduct({ body: formData, id: product.id }).unwrap();
      if (res) {
        toast.success(res.message || 'Product updated successfully');
        onOpenChange(false);
      }
    } catch (error) {
      toast.error('Failed to update product');
      console.error('Update error:', error);
    }
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Edit Product</DialogTitle>
          <DialogDescription>Update the details for the product.</DialogDescription>
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
            {/* <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='barcode' className='text-right'>
                Barcode
              </Label>
              <Input
                id='barcode'
                value={formData.barcode}
                onChange={(e) => handleChange('barcode', e.target.value)}
                className='col-span-3'
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
              <Label htmlFor='product_category_id' className='text-right'>
                Category
              </Label>
              <Select value={formData.product_category_id} onValueChange={(value) => handleChange('product_category_id', value)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>
                <SelectContent>
                  {categories?.categories.map((cat: any) => (
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
            <Button type='submit'>{isLoading ? <LoadingState /> : 'Update Product'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
