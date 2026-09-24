import React, { useState, useEffect } from 'react';
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
import { Switch } from '@/components/ui/switch';
import { useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { useTaxCategoriesQuery } from '@/app/store/features/business/tax/taxQuery';
import { toast } from 'sonner';
import { useUpdateProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { LoadingState } from '@/utils/LoadingState';

interface EditProductProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  product: any;
}

export const EditProduct: React.FC<EditProductProps> = ({ open, onOpenChange, product }) => {
  const { data: categoriesData } = useProductCategoriesQuery();
  const categories = categoriesData?.categories ?? [];
  const { data: taxCategoriesData } = useTaxCategoriesQuery();
  const taxCategories = (taxCategoriesData?.categories ?? []).filter((c: any) => c.is_active);
  const [updateProduct, { isLoading }] = useUpdateProductMutation();

  const EMOJIS = ['📱', '💻', '🖥️', '🎧', '📷', '📺', '🎮', '⌚', '🏠', '📡', '🔌', '🖨️', '📞', '🔋', '💾', '🖱️'];

  const [formData, setFormData] = useState({
    name: '',
    barcode: '',
    selling_price: '',
    cost_price: '',
    quantity: '',
    minimum_stock: '',
    status: 'active',
    description: '',
    emoji: '',
    product_category_id: '',
    tax_category_id: '',
    is_tax_inclusive: false,
  });

  useEffect(() => {
    if (product) {
      setFormData({
        name: product.name || '',
        barcode: product.barcode || '',
        selling_price: product.selling_price?.toString() || '',
        cost_price: product.cost_price?.toString() || '',
        quantity: product.quantity?.toString() || '',
        minimum_stock: product.minimum_stock?.toString() || product.reorder_level?.toString() || '',
        status: product.status === true || product.status === 'active' ? 'active' : 'inactive',
        description: product.description || '',
        emoji: product.emoji || '',
        product_category_id: product.product_category_id || '',
        tax_category_id: product.tax_category_id ? String(product.tax_category_id) : '',
        is_tax_inclusive: !!product.is_tax_inclusive,
      });
    }
  }, [product]);

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await updateProduct({ body: formData, id: product.id }).unwrap();
      toast.success(res?.message || 'Product updated successfully');
      onOpenChange(false);
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
      <DialogContent className='sm:max-w-2xl max-h-[90vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Edit Product</DialogTitle>
          <DialogDescription>Update the details for the product and keep its category in sync.</DialogDescription>
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
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='barcode' className='text-right'>
                Barcode
              </Label>
              <Input
                id='barcode'
                value={formData.barcode}
                onChange={(e) => handleChange('barcode', e.target.value)}
                className='col-span-3'
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='selling_price' className='text-right'>
                Selling Price
              </Label>
              <Input
                id='selling_price'
                type='number'
                value={formData.selling_price}
                onChange={(e) => handleChange('selling_price', e.target.value)}
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
              <Label htmlFor='minimum_stock' className='text-right'>
                Min Stock
              </Label>
              <Input
                id='minimum_stock'
                type='number'
                value={formData.minimum_stock}
                onChange={(e) => handleChange('minimum_stock', e.target.value)}
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
                  <SelectValue placeholder='Select status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='active'>Active</SelectItem>
                  <SelectItem value='inactive'>Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='product_category_id' className='text-right'>
                Category
              </Label>
              <Select
                value={formData.product_category_id}
                onValueChange={(value) => handleChange('product_category_id', value)}
              >
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((cat: any) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='tax_category_id' className='text-right'>
                Tax Category
              </Label>
              <Select
                value={formData.tax_category_id}
                onValueChange={(value) => handleChange('tax_category_id', value)}
              >
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='No tax (optional)' />
                </SelectTrigger>
                <SelectContent>
                  {taxCategories.map((cat: any) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='is_tax_inclusive' className='text-right'>
                Tax Inclusive
              </Label>
              <div className='col-span-3 flex items-center gap-2'>
                <Switch
                  id='is_tax_inclusive'
                  checked={formData.is_tax_inclusive}
                  onCheckedChange={(checked) => setFormData((p) => ({ ...p, is_tax_inclusive: checked }))}
                />
                <span className='text-sm text-muted-foreground'>Selling price already includes tax</span>
              </div>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Emoji</Label>
              <div className='col-span-3 flex flex-wrap gap-2'>
                {EMOJIS.map((emoji) => (
                  <button
                    key={emoji}
                    type='button'
                    onClick={() => handleChange('emoji', emoji === formData.emoji ? '' : emoji)}
                    className={`text-2xl p-2 rounded-xl border transition-all ${
                      formData.emoji === emoji
                        ? 'border-primary bg-primary/10 scale-110'
                        : 'border-border hover:border-primary/50'
                    }`}
                  >
                    {emoji}
                  </button>
                ))}
              </div>
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
