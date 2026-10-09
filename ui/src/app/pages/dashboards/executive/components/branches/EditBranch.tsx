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
import { Pencil } from 'lucide-react';
import { useUpdateBranchMutation } from '@/app/store/features/business/branches/branchesQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';
import { serverMessage } from '@/app/utils/errorMessage';

export interface EditableBranch {
  id: number | string;
  name?: string;
  address?: string;
  phone?: string;
}

export const EditBranch = ({ branch }: { branch: EditableBranch }) => {
  const [open, setOpen] = useState(false);
  const [updateBranch, { isLoading }] = useUpdateBranchMutation();
  const [formData, setFormData] = useState({
    name: branch.name ?? '',
    address: branch.address ?? '',
    phone: branch.phone ?? '',
  });

  const handleOpenChange = (next: boolean) => {
    // Re-read the branch each time the dialog opens, so a save that changed it
    // elsewhere is not edited through the values of a previous visit.
    if (next) {
      setFormData({
        name: branch.name ?? '',
        address: branch.address ?? '',
        phone: branch.phone ?? '',
      });
    }
    setOpen(next);
  };

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await updateBranch({ body: formData, id: branch.id }).unwrap();
      if (res) {
        toast.success(res.message ?? 'Branch updated');
        setOpen(false);
      }
      return;
    } catch (error) {
      toast.error(serverMessage(error, 'Failed to update the branch!'));
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button variant='outline'>
          <Pencil className='h-4 w-4 mr-2' />
          Edit Branch
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Edit Branch</DialogTitle>
          <DialogDescription>Update the details of this branch.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='edit-branch-name' className='text-right'>
                Name
              </Label>
              <Input
                id='edit-branch-name'
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='edit-branch-address' className='text-right'>
                Address
              </Label>
              <Input
                id='edit-branch-address'
                value={formData.address}
                onChange={(e) => handleChange('address', e.target.value)}
                className='col-span-3'
                required
              />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='edit-branch-phone' className='text-right'>
                Phone
              </Label>
              <Input
                id='edit-branch-phone'
                type='text'
                value={formData.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                className='col-span-3'
                required
              />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>{isLoading ? <LoadingState /> : 'Save Changes'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
