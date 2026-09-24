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
import { Switch } from '@/components/ui/switch';
import { Plus } from 'lucide-react';
import { useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { useTaxCategoriesQuery } from '@/app/store/features/business/tax/taxQuery';
import { toast } from 'sonner';

interface AddProductProps {
  addProduct: any;
}

export const AddProduct: React.FC<AddProductProps> = ({ addProduct }) => {
  const [open, setOpen] = useState(false);
  const { data } = useProductCategoriesQuery();
  const { data: taxCategoriesData } = useTaxCategoriesQuery();
  const taxCategories = (taxCategoriesData?.categories ?? []).filter((c: any) => c.is_active);

  const EMOJIS = ['📱', '💻', '🖥️', '🎧', '📷', '📺', '🎮', '⌚', '🏠', '📡', '🔌', '🖨️', '📞', '🔋', '💾', '🖱️'];

  const [formData, setFormData] = useState({
    name: '',
    sku: '',
    barcode: '',
    selling_price: '',
    cost_price: '',
    quantity: '',
    reorder_level: '',
    description: '',
    emoji: '',
    product_category_id: '',
    tax_category_id: '',
    is_tax_inclusive: false,
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await addProduct(formData).unwrap();
      if (res) {
        toast.success(res.message || 'Product added successfully');
        setOpen(false);
        setFormData({
          name: '',
          sku: '',
          barcode: '',
          selling_price: '',
          cost_price: '',
          quantity: '',
          reorder_level: '',
          description: '',
          emoji: '',
          product_category_id: '',
          tax_category_id: '',
          is_tax_inclusive: false,
        });
      }
    } catch (error) {
      toast.error('Failed to add product');
      console.error(error);
    }
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
      <DialogContent className='sm:max-w-106.25 max-h-[90vh] overflow-y-auto'>
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
            <div className='grid grid-cols-4 items-center gap-4'>
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
                  {data?.categories.map((cat: any) => (
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
            <Button type='submit'>Add Product</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
