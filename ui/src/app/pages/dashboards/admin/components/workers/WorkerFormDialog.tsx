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
import { useRegisterWorkerMutation, useUpdateWorkerMutation } from '@/app/store/features/business/workers/workersQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

type WorkerFormData = {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  role_id: string;
  business_branch_id: string;
  // Optional fields you might want later
  department?: string;
  position?: string;
  employment_type?: string;
};

type WorkerFormDialogProps = {
  open: boolean;
  roles: any[];
  branches: any[];
  selectedWorker: any; // null when creating
  setDialogOpen: (open: boolean) => void;
  onOpenChange: (open: boolean) => void;
};

export const WorkerFormDialog = ({
  selectedWorker,
  open,
  onOpenChange,
  roles,
  branches,
  setDialogOpen,
}: WorkerFormDialogProps) => {
  const [registerWorker] = useRegisterWorkerMutation();
  const [updateWorker, { error }] = useUpdateWorkerMutation();
  const worker = selectedWorker?.user;

  console.log('selectedWorker==>', worker);
  const [formData, setFormData] = useState<WorkerFormData>({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    role_id: '',
    business_branch_id: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  // Reset form when dialog opens/closes or worker changes
  useEffect(() => {
    if (worker) {
      setFormData({
        firstname: worker.firstname || worker.name?.split(' ')[0] || '',
        lastname: worker.lastname || worker.name?.split(' ').slice(1).join(' ') || '',
        email: worker.email || '',
        phone: worker.phone || '',
        role_id: worker.role_id?.toString() || '',
        business_branch_id: worker.business_branch_id?.toString() || '',
      });
    } else {
      setFormData({
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        role_id: '',
        business_branch_id: '',
      });
    }
  }, [worker, open]);
  console.log('error==>', error);
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      const payload = {
        ...formData,
        // You can add defaults here if needed
      };

      if (selectedWorker) {
        // Update
        const res = await updateWorker({
          id: selectedWorker.id,
          body: payload,
        }).unwrap();

        toast.success(res.message || 'Worker updated successfully');
      } else {
        // Create
        console.log('now creating...');
        const res = await registerWorker(payload).unwrap();
        toast.success(res.message || 'Worker created successfully');
      }

      setDialogOpen(false);
    } catch (error: any) {
      const message = error?.data?.message || 'Failed to save worker';
      toast.error(message);
      console.error('Worker save error:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const isLoading = isSubmitting;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-w-lg'>
        <DialogHeader>
          <DialogTitle>{selectedWorker ? 'Edit Worker' : 'Add New Worker'}</DialogTitle>
          <DialogDescription>
            {selectedWorker
              ? 'Update worker information and role'
              : 'Create a new worker account with role and branch assignment'}
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
                placeholder='Jane'
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
              placeholder='jane@company.com'
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
            <Label htmlFor='role_id'>Role</Label>
            <select
              id='role_id'
              name='role_id'
              value={formData.role_id}
              onChange={handleChange}
              className='h-11 rounded-lg border border-input bg-background px-3 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10'
              required
            >
              <option value='' disabled>
                Choose a role
              </option>
              {roles?.map((role) => (
                <option key={role.id} value={role.id}>
                  {role.name}
                </option>
              ))}
            </select>
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='business_branch_id'>Branch</Label>
            <select
              id='business_branch_id'
              name='business_branch_id'
              value={formData.business_branch_id}
              onChange={handleChange}
              className='h-11 rounded-lg border border-input bg-background px-3 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10'
              required
            >
              <option value='' disabled>
                Choose a branch
              </option>
              {branches?.map((branch) => (
                <option key={branch.id} value={branch.id}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>

          <DialogFooter className='mt-6'>
            <DialogClose asChild>
              <Button type='button' variant='outline' disabled={isLoading}>
                Cancel
              </Button>
            </DialogClose>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? <LoadingState /> : selectedWorker ? 'Update Worker' : 'Create Worker'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
