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
import { Plus, ImagePlus, Loader2 } from 'lucide-react';
import { useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { useTaxCategoriesQuery } from '@/app/store/features/business/tax/taxQuery';
import { useUploadProductAttachmentMutation } from '@/app/store/features/branch/attachments/attachmentsQuery';
import { toast } from 'sonner';

interface AddProductProps {
  addProduct: any;
}

export const AddProduct: React.FC<AddProductProps> = ({ addProduct }) => {
  const [open, setOpen] = useState(false);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const { data: categoriesData } = useProductCategoriesQuery();
  const categories = categoriesData?.categories ?? [];
  const { data: taxCategoriesData } = useTaxCategoriesQuery();
  const taxCategories = (taxCategoriesData?.categories ?? []).filter((c: any) => c.is_active);
  const [uploadImage, { isLoading: isUploadingImage }] = useUploadProductAttachmentMutation();

  const EMOJIS = ['📱', '💻', '🖥️', '🎧', '📷', '📺', '🎮', '⌚', '🏠', '📡', '🔌', '🖨️', '📞', '🔋', '💾', '🖱️'];

  const [formData, setFormData] = useState({
    name: '',
    barcode: '',
    cost_price: '',
    selling_price: '',
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
      const res = await addProduct({
        ...formData,
        quantity: Number(formData.quantity),
        reorder_level: Number(formData.reorder_level),
      }).unwrap();

      const productId = res?.product?.id;
      if (productId && imageFile) {
        try {
          await uploadImage({ productId, file: imageFile }).unwrap();
        } catch {
          toast.warning('Product created but image upload failed');
        }
      }

      toast.success(res?.message || 'Product added successfully');
      setOpen(false);
      setImageFile(null);
      setFormData({
        name: '',
        barcode: '',
        cost_price: '',
        selling_price: '',
        quantity: '',
        reorder_level: '',
        description: '',
        emoji: '',
        product_category_id: '',
        tax_category_id: '',
        is_tax_inclusive: false,
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
      <DialogContent className='sm:max-w-2xl max-h-[90vh] overflow-y-auto'>
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
                <SelectTrigger id='product_category_id' className='col-span-3'>
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
                <SelectTrigger id='tax_category_id' className='col-span-3'>
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
              <Label className='text-right'>Image</Label>
              <div className='col-span-3 flex items-center gap-3'>
                {imageFile ? (
                  <img
                    src={URL.createObjectURL(imageFile)}
                    alt='preview'
                    className='h-14 w-14 rounded-lg border object-cover'
                  />
                ) : null}
                <Button
                  type='button'
                  variant='outline'
                  size='sm'
                  onClick={() => document.getElementById('product-image-input')?.click()}
                  disabled={isUploadingImage}
                >
                  {isUploadingImage ? <Loader2 className='h-4 w-4 animate-spin' /> : <ImagePlus className='h-4 w-4' />}
                  {imageFile ? 'Change image' : 'Upload image'}
                </Button>
                {imageFile ? (
                  <Button type='button' variant='ghost' size='sm' onClick={() => setImageFile(null)}>
                    Remove
                  </Button>
                ) : null}
                <input
                  id='product-image-input'
                  type='file'
                  accept='image/*'
                  className='hidden'
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    e.target.value = '';
                    if (file) setImageFile(file);
                  }}
                />
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
