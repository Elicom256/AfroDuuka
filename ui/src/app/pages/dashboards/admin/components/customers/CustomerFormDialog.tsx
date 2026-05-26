// components/customers/CustomerFormDialog.tsx
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
  useAddCustomerMutation,
  useUpdateCustomerMutation,
} from '@/app/store/features/business/customers/customersQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

type CustomerFormData = {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  company_name?: string;
  remarks?: string;
};

type CustomerFormDialogProps = {
  open: boolean;
  selectedCustomer: any; // null when creating
  setDialogOpen: (open: boolean) => void;
  onOpenChange: (open: boolean) => void;
};

export const CustomerFormDialog = ({
  selectedCustomer,
  open,
  onOpenChange,
  setDialogOpen,
}: CustomerFormDialogProps) => {
  const [registerCustomer] = useAddCustomerMutation();
  const [updateCustomer] = useUpdateCustomerMutation();

  const [formData, setFormData] = useState<CustomerFormData>({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    company_name: '',
    remarks: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  // Populate form when editing
  useEffect(() => {
    if (selectedCustomer) {
      setFormData({
        firstname: selectedCustomer.firstname || selectedCustomer.name?.split(' ')[0] || '',
        lastname: selectedCustomer.lastname || selectedCustomer.name?.split(' ').slice(1).join(' ') || '',
        email: selectedCustomer.email || '',
        phone: selectedCustomer.phone || '',
        company_name: selectedCustomer.company_name || '',
        remarks: selectedCustomer.remarks || '',
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
  }, [selectedCustomer, open]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (selectedCustomer) {
        const res = await updateCustomer({
          id: selectedCustomer.id,
          body: formData,
        }).unwrap();

        toast.success(res.message || 'Customer updated successfully');
      } else {
        const res = await registerCustomer(formData).unwrap();
        console.log('customer Response==>', res);
        toast.success(res.message || 'Customer created successfully');
      }

      setDialogOpen(false);
    } catch (error: any) {
      const message = error?.data?.message || 'Failed to save customer';
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-w-lg'>
        <DialogHeader>
          <DialogTitle>{selectedCustomer ? 'Edit Customer' : 'Add New Customer'}</DialogTitle>
          <DialogDescription>
            {selectedCustomer ? 'Update customer information' : 'Create a new customer account'}
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
              placeholder='customer@company.com'
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
              placeholder='Acme Corporation'
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='remarks'>Remarks / Notes</Label>
            <textarea
              id='remarks'
              name='remarks'
              value={formData.remarks}
              onChange={handleChange}
              className='min-h-20 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'
              placeholder='Additional information about this customer...'
            />
          </div>

          <DialogFooter className='mt-6'>
            <DialogClose asChild>
              <Button type='button' variant='outline' disabled={isSubmitting}>
                Cancel
              </Button>
            </DialogClose>
            <Button type='submit' disabled={isSubmitting}>
              {isSubmitting ? <LoadingState /> : selectedCustomer ? 'Update Customer' : 'Create Customer'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
