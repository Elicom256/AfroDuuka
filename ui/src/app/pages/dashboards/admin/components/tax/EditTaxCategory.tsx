import { useState } from 'react';
import { toast } from 'sonner';
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
import { Pencil } from 'lucide-react';
import { useUpdateTaxCategoryMutation } from '@/app/store/features/business/tax/taxQuery';

type EditTaxCategoryProps = {
  category: any;
};

export const EditTaxCategory = ({ category }: EditTaxCategoryProps) => {
  const [open, setOpen] = useState(false);
  const [updateCategory] = useUpdateTaxCategoryMutation();
  const [formData, setFormData] = useState({ name: '', description: '', is_active: true });

  const handleEdit = () => {
    setFormData({
      name: category.name ?? '',
      description: category.description ?? '',
      is_active: category.is_active ?? true,
    });
    setOpen(true);
  };

  const handleUpdate = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await updateCategory({ id: category.id, body: formData }).unwrap();
      toast.success(res?.message || 'Tax category updated');
      setOpen(false);
    } catch {
      toast.error('Failed to update tax category');
    }
  };

  return (
    <>
      <Button variant='ghost' size='icon' onClick={handleEdit}>
        <Pencil className='h-4 w-4' />
      </Button>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Tax Category</DialogTitle>
            <DialogDescription>Update the tax category details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate}>
            <div className='grid gap-4 py-4'>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-name' className='text-right'>Name</Label>
                <Input id='edit-name' value={formData.name} onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-description' className='text-right'>Description</Label>
                <Textarea id='edit-description' value={formData.description} onChange={(e) => setFormData((p) => ({ ...p, description: e.target.value }))} className='col-span-3' />
              </div>
            </div>
            <DialogFooter>
              <Button type='submit'>Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
};