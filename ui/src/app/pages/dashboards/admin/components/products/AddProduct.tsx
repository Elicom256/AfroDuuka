import React, { useEffect, useState } from 'react';
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
import { useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { toast } from 'sonner';

interface AddProductProps {
  addProduct: any;
}

export const AddProduct: React.FC<AddProductProps> = ({ addProduct }) => {
  const [open, setOpen] = useState(false);
  const { data: categoriesData } = useProductCategoriesQuery();
  const categories = categoriesData?.categories ?? [];

  const EMOJIS = ['📱', '💻', '🖥️', '🎧', '📷', '📺', '🎮', '⌚', '🏠', '📡', '🔌', '🖨️', '📞', '🔋', '💾', '🖱️'];

  const [formData, setFormData] = useState({
    name: '',
    markup_percentage: '',
    cost_price: '',
    price: '',
    quantity: '',
    reorder_level: '',
    description: '',
    emoji: '',
    product_category_id: '',
  });

  useEffect(() => {
    const cost = Number(formData.cost_price) || 0;
    const markup = Number(formData.markup_percentage) || 0;
    const calculatedPrice = cost + (cost * markup) / 100;

    setFormData((prev) => ({
      ...prev,
      price: calculatedPrice.toString(),
    }));
  }, [formData.cost_price, formData.markup_percentage]);

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    try {
      const res = await addProduct({
        ...formData,
        quantity: Number(formData.quantity),
        reorder_level: Number(formData.reorder_level),
      }).unwrap();

      toast.success(res?.message || 'Product added successfully');
      setOpen(false);
      setFormData({
        name: '',
        markup_percentage: '',
        cost_price: '',
        price: '',
        quantity: '',
        reorder_level: '',
        description: '',
        emoji: '',
        product_category_id: '',
      });
    } catch (error) {
      console.error('Add product error:', error);
      toast.error('Failed to add product');
    }
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='mr-2 h-4 w-4' />
          Add Product
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-2xl'>
        <DialogHeader>
          <DialogTitle>Add New Product</DialogTitle>
          <DialogDescription>Enter the details for the new branch product and link it to a category.</DialogDescription>
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
              <Label htmlFor='markup_percentage' className='text-right'>
                Markup Percentage
              </Label>
              <Input
                id='markup_percentage'
                type='number'
                value={formData.markup_percentage}
                onChange={(e) => handleChange('markup_percentage', e.target.value)}
                className='col-span-3'
                required
              />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='price' className='text-right'>
                Selling Price
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
              <Select value={formData.product_category_id} onValueChange={(value) => handleChange('product_category_id', value)}>
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
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Emoji</Label>
              <div className='col-span-3 flex flex-wrap gap-2'>
                {EMOJIS.map((emoji) => (
                  <button
                    key={emoji}
                    type='button'
                    onClick={() => handleChange('emoji', emoji === formData.emoji ? '' : emoji)}
                    className={`text-2xl p-2 rounded-xl border transition-all ${
                      formData.emoji === emoji ? 'border-primary bg-primary/10 scale-110' : 'border-border hover:border-primary/50'
                    }`}
                  >
                    {emoji}
                  </button>
                ))}
              </div>
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
