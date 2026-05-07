import { useState } from 'react';
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
import { useRegisterWorkerMutation } from '@/app/store/features/business/workers/workersQuery';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

type WorkerFormDialogProps = {
  open: boolean;
  roles: any;
  onOpenChange: (open: boolean) => void;
  onSubmit: (values: { name: string; email: string; phone: string; role: string }) => void | Promise<void>;
  isLoading?: boolean;
};

export const WorkerFormDialog = ({ open, onOpenChange, roles }: WorkerFormDialogProps) => {
  const [register, { isLoading }] = useRegisterWorkerMutation();
  const [worker, setWorker] = useState({
    name: '',
    email: '',
    phone: '',
    role_id: 0,
  });
  console.log('roles==>', roles);

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
      const res = await register(worker).unwrap();
      if (res) {
        toast.success(res.message);
        setWorker({ name: '', email: '', phone: '', role_id: 0 });
      }
      console.log('Worker data==>', res);
      return res;
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

          <DialogFooter>
            <DialogClose asChild>
              <Button type='button' variant='outline'>
                Cancel
              </Button>
            </DialogClose>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? <LoadingState /> : 'Save changes'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
