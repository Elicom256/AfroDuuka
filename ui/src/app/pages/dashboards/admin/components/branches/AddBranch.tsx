import { useState } from 'react';

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
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { useAddBranchMutation } from '@/app/store/features/business/branches/branchesQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';
export const AddBranch = () => {
  const [open, setOpen] = useState(false);
  const [addBranch, { isLoading, error }] = useAddBranchMutation();
  const [formData, setFormData] = useState({
    name: '',
    address: '',
    phone: '',
  });
  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };
  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await addBranch(formData).unwrap();
      console.log("res==>", res)
      if (res) {
        toast.success(res.message ?? 'Added a new branch');
        setOpen(false);
      }
      return;
    } catch (error) {
      console.log('Error here==>', error);
      toast.error('Failed to add a branch!');
    }
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
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='address' className='text-right'>
                address
              </Label>
              <Input
                id='address'
                value={formData.address}
                onChange={(e) => handleChange('address', e.target.value)}
                className='col-span-3'
                required
              />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='phone' className='text-right'>
                phone
              </Label>
              <Input
                id='phone'
                type='text'
                value={formData.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
          </div>
          {/* <div className='text-center text-red-400'>{error && (error?.data?.message as string)}</div> */}
          <DialogFooter>
            <Button type='submit'>{isLoading ? <LoadingState /> : 'Add Branch'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
