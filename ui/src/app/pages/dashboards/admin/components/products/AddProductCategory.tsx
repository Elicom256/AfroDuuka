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
import {
  useAddProductCategoryMutation,
  useProductCategoriesQuery,
} from '@/app/store/features/business/products/productsQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

interface AddProductCategoryProps {}

export const AddProductCategory: React.FC<AddProductCategoryProps> = () => {
  const [open, setOpen] = useState(false);
  const [addCategory, { isLoading }] = useAddProductCategoryMutation();
  const { data: cats } = useProductCategoriesQuery();
  const categories = cats?.categories ?? [];
  console.log('categoryProducts==>', categories);
  const [formData, setFormData] = useState<any>({
    name: '',
    description: '',
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    // TODO: Call addCategory mutation
    const res = await addCategory(formData).unwrap();
    console.log('Category res==>', res);
    if (res) {
      toast.success(res.message);
    }
    console.log('Adding category:', formData);
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
          Add Product Category
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Add New Product Category</DialogTitle>
          <DialogDescription>Enter the details for the new product category.</DialogDescription>
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
