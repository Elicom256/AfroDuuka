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
import { Plus } from 'lucide-react';
import { useAddProductMutation, useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface AddProductTypeProps {}
export const AddBusinessProduct: React.FC<AddProductTypeProps> = () => {
  const [open, setOpen] = useState(false);
  const [addProductType, { isLoading }] = useAddProductMutation();
  const { data: cats } = useProductCategoriesQuery();
  const categories = cats?.categories ?? [];
  console.log('categoryProducts==>', categories);
  const [formData, setFormData] = useState<any>({
    name: '',
    description: '',
    category_id: '',
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    const res = await addProductType(formData).unwrap();
    console.log('product type res==>', res);
    if (res) {
      toast.success(res.message);
    }
    console.log('Adding type:', formData);
    setOpen(false);
  };
  const handleChange = (field: string, value: string) => {
    setFormData((prev: any) => ({ ...prev, [field]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant='outline'>
          <Plus className='h-4 w-4 mr-2' />
          Add Product Type
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Add New Product Type</DialogTitle>
          <DialogDescription>Enter the details for the new product type.</DialogDescription>
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
              <Label htmlFor='category_id' className='text-right'>
                Category
              </Label>
              <Select value={formData.category_id} onValueChange={(value) => handleChange('category_id', value)}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='Select category' />
                </SelectTrigger>
                <SelectContent>
                  {categories?.map((cat: any) => (
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
