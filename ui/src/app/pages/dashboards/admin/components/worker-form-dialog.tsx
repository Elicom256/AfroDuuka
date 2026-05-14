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

type WorkerFormDialogProps = {
  open: boolean;
  roles: any;
  branches: any[];
  selectedWorker: any;
  setDialogOpen: any;
  onOpenChange: (open: boolean) => void;
  // onSubmit: (values: { name: string; email: string; phone: string; role: string }) => void | Promise<void>;
  // isLoading?: boolean;
};

export const WorkerFormDialog = ({
  selectedWorker,
  open,
  onOpenChange,
  roles,
  branches,
  setDialogOpen,
}: WorkerFormDialogProps) => {
  const [register, { isLoading }] = useRegisterWorkerMutation();
  const [updateWorker, { isLoading: isUpdating }] = useUpdateWorkerMutation();

  const [worker, setWorker] = useState({
    name: '',
    email: '',
    phone: '',
    role_id: '',
    business_branch_id: '',
  });
  useEffect(() => {
    if (selectedWorker) {
      setWorker({
        name: selectedWorker.name || '',
        email: selectedWorker.email ?? '',
        phone: selectedWorker.phone ?? '',
        role_id: selectedWorker.role_id ?? '',
        business_branch_id: selectedWorker.business_branch_id ?? '',
      });
      // console.log('selectedWorker==>', selectedWorker);
    }
  }, [selectedWorker]);
  console.log('selectedWorker==>', selectedWorker);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setWorker((prev) => ({
      ...prev,
      [name]: name === 'role' ? Number(value) : name !== 'name' ? value.trim() : value,
    }));
    console.log(value);
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      if (selectedWorker) {
        const res = await updateWorker({ id: selectedWorker.id, userData: worker }).unwrap();
        console.log('Update res==>', res);
        if (res) {
          toast.success(res.message || 'Worker updated successfully!.');
          setDialogOpen(false);
          return;
        }
      } else {
        console.log('Hitting a wrong endpoint');
        const res = await register(worker).unwrap();
        if (res) {
          toast.success(res.message);
          setWorker({ name: '', email: '', phone: '', role_id: '', business_branch_id: '' });
          setDialogOpen(false);
        }
        console.log('Worker data==>', res);
        return res;
      }
    } catch (error) {
      toast.error('Failed to add worker!');
      return console.log('error==>', error);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-w-lg'>
        <DialogHeader>
          <DialogTitle>Add worker</DialogTitle>
          <DialogDescription>Create a new worker and assign a role. </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='grid gap-4'>
          <div className='grid gap-2'>
            <Label htmlFor='worker-name'>Name</Label>
            <Input
              id='name'
              name='name'
              value={worker.name}
              onChange={handleInputChange}
              placeholder='Jane Doe'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-email'>Email</Label>
            <Input
              id='email'
              type='email'
              name='email'
              value={worker.email}
              onChange={handleInputChange}
              placeholder='jane@example.com'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-phone'>Phone</Label>
            <Input
              id='phone'
              type='tel'
              name='phone'
              value={worker.phone}
              onChange={handleInputChange}
              placeholder='+254 700 000 000'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-role'>Role</Label>
            <select
              id='role'
              name='role_id'
              value={worker.role_id}
              onChange={handleInputChange}
              className='h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10'
              required
            >
              <option value='' disabled>
                Choose a role
              </option>
              {roles?.map((role: any) => (
                <option key={role.id} value={role.id}>
                  {role.name}
                </option>
              ))}
            </select>
          </div>
          {/* branch */}
          <div className='grid gap-2'>
            <Label htmlFor='worker-role'>Branch</Label>
            <select
              id='branch'
              name='business_branch_id'
              value={worker.business_branch_id}
              onChange={handleInputChange}
              className='h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10'
              required
            >
              <option value='' disabled>
                Choose a branch
              </option>
              {branches?.map((branch: any) => (
                <option key={branch.id} value={branch.id}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>
          <DialogFooter>
            <DialogClose asChild>
              <Button type='button' variant='outline'>
                Cancel
              </Button>
            </DialogClose>
            <Button type='submit' disabled={isLoading}>
              {isLoading || isUpdating ? <LoadingState /> : selectedWorker ? 'Update Worker' : 'Save changes'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
