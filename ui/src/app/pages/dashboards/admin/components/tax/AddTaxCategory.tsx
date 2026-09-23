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
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus } from 'lucide-react';
import { useAddTaxCategoryMutation } from '@/app/store/features/business/tax/taxQuery';

export const AddTaxCategory = () => {
  const [open, setOpen] = useState(false);
  const [addCategory] = useAddTaxCategoryMutation();
  const [formData, setFormData] = useState({ name: '', description: '', is_active: true });

  const resetForm = () => setFormData({ name: '', description: '', is_active: true });

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await addCategory(formData).unwrap();
      toast.success(res?.message || 'Tax category created');
      setOpen(false);
      resetForm();
    } catch {
      toast.error('Failed to create tax category');
    }
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { setOpen(o); if (!o) resetForm(); }}>
      <DialogTrigger asChild>
        <Button variant='outline' size='sm'>
          <Plus className='mr-2 h-4 w-4' />
          Add Tax Category
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Tax Category</DialogTitle>
          <DialogDescription>Define a tax type your business is obligated to handle (e.g. VAT, PAYE).</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='name' className='text-right'>Name</Label>
              <Input id='name' value={formData.name} onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='description' className='text-right'>Description</Label>
              <Textarea id='description' value={formData.description} onChange={(e) => setFormData((p) => ({ ...p, description: e.target.value }))} className='col-span-3' />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Create</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};