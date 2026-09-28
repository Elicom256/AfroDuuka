// components/suppliers/SupplierFormDialog.tsx
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  useAddSupplierMutation,
  useUpdateSupplierMutation,
} from '@/app/store/features/business/suppliers/supplierQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

type SupplierFormData = {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  company_name?: string;
  remarks?: string;
};

type SupplierFormDialogProps = {
  open: boolean;
  selectedSupplier: any; // null when creating
  setDialogOpen: (open: boolean) => void;
  onOpenChange: (open: boolean) => void;
};

export const SupplierFormDialog = ({
  selectedSupplier,
  open,
  onOpenChange,
  setDialogOpen,
}: SupplierFormDialogProps) => {
  const [registerSupplier] = useAddSupplierMutation();
  const [updateSupplier] = useUpdateSupplierMutation();

  const [formData, setFormData] = useState<SupplierFormData>({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    company_name: '',
    remarks: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  console.log('selectedSupplier==>', selectedSupplier);
  const supplier = selectedSupplier?.user;
  useEffect(() => {
    if (selectedSupplier) {
      setFormData({
        firstname: supplier.firstname || supplier.name?.split(' ')[0] || '',
        lastname: supplier.lastname || supplier.name?.split(' ').slice(1).join(' ') || '',
        email: supplier.email || '',
        phone: supplier.phone || '',
        company_name: selectedSupplier.company_name || '',
        remarks: selectedSupplier.remarks || '',
      });
    } else {
      setFormData({
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        company_name: '',
        remarks: '',
      });
    }
  }, [selectedSupplier, open]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (selectedSupplier) {
        const res = await updateSupplier({
          id: selectedSupplier.id,
          body: formData,
        }).unwrap();

        toast.success(res.message || 'Supplier updated successfully');
      } else {
        const res = await registerSupplier(formData).unwrap();
        toast.success(res.message || 'Supplier created successfully');
      }

      setDialogOpen(false);
    } catch (error: any) {
      console.log('Error==>', error);
      const message = error?.data?.message || 'Failed to save supplier';
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-w-lg'>
        <DialogHeader>
          <DialogTitle>{selectedSupplier ? 'Edit Supplier' : 'Add New Supplier'}</DialogTitle>
          <DialogDescription>
            {selectedSupplier ? 'Update supplier information' : 'Create a new supplier with company details'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='grid gap-4 py-2'>
          <div className='grid grid-cols-2 gap-4'>
            <div className='grid gap-2'>
              <Label htmlFor='firstname'>First Name</Label>
              <Input
                id='firstname'
                name='firstname'
                value={formData.firstname}
                onChange={handleChange}
                placeholder='John'
                required
              />
            </div>
            <div className='grid gap-2'>
              <Label htmlFor='lastname'>Last Name</Label>
              <Input
                id='lastname'
                name='lastname'
                value={formData.lastname}
                onChange={handleChange}
                placeholder='Doe'
                required
              />
            </div>
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='email'>Email Address</Label>
            <Input
              id='email'
              type='email'
              name='email'
              value={formData.email}
              onChange={handleChange}
              placeholder='supplier@company.com'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='phone'>Phone Number</Label>
            <Input
              id='phone'
              type='tel'
              name='phone'
              value={formData.phone}
              onChange={handleChange}
              placeholder='+256 700 123 456'
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='company_name'>Company Name</Label>
            <Input
              id='company_name'
              name='company_name'
              value={formData.company_name}
              onChange={handleChange}
              placeholder='Acme Supplies Ltd'
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='remarks'>Remarks / Notes</Label>
            <Textarea
              id='remarks'
              name='remarks'
              value={formData.remarks}
              onChange={handleChange}
              placeholder='Additional information about this supplier...'
              className='min-h-20'
            />
          </div>

          <DialogFooter className='mt-6'>
            <DialogClose asChild>
              <Button type='button' variant='outline' disabled={isSubmitting}>
                Cancel
              </Button>
            </DialogClose>
            <Button type='submit' disabled={isSubmitting}>
              {isSubmitting ? <LoadingState /> : selectedSupplier ? 'Update Supplier' : 'Create Supplier'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
