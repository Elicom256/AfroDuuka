import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
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
import { Loader2Icon } from 'lucide-react';

type WorkerFormDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  defaultValues?: {
    id?: number | string;
    name?: string;
    email?: string;
    phone?: string;
    role?: string;
  } | null;
  onSubmit: (values: { name: string; email: string; phone: string; role: string }) => void | Promise<void>;
  isLoading?: boolean;
};

const workerRoles = ['Sales', 'Support', 'Operations', 'Inventory', 'Finance', 'Admin'];

export const WorkerFormDialog = ({ open, onOpenChange, defaultValues, onSubmit, isLoading }: WorkerFormDialogProps) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState('');

  useEffect(() => {
    if (open) {
      setName(defaultValues?.name ?? '');
      setEmail(defaultValues?.email ?? '');
      setPhone(defaultValues?.phone ?? '');
      setRole(defaultValues?.role ?? '');
    }
  }, [defaultValues, open]);

  const isEdit = Boolean(defaultValues?.id);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    await onSubmit({
      name: name.trim(),
      email: email.trim(),
      phone: phone.trim(),
      role: role.trim(),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-w-lg'>
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Edit worker' : 'Add worker'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update worker details and save changes.' : 'Create a new worker and assign a role.'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='grid gap-4'>
          <div className='grid gap-2'>
            <Label htmlFor='worker-name'>Name</Label>
            <Input
              id='worker-name'
              value={name}
              onChange={(event) => setName(event.target.value)}
              placeholder='Jane Doe'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-email'>Email</Label>
            <Input
              id='worker-email'
              type='email'
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder='jane@example.com'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-phone'>Phone</Label>
            <Input
              id='worker-phone'
              type='tel'
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
              placeholder='+254 700 000 000'
              required
            />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='worker-role'>Role</Label>
            <select
              id='worker-role'
              value={role}
              onChange={(event) => setRole(event.target.value)}
              className='h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10'
              required
            >
              <option value='' disabled>
                Choose a role
              </option>
              {workerRoles.map((roleOption) => (
                <option key={roleOption} value={roleOption}>
                  {roleOption}
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
              {isLoading ? <Loader2Icon className='mr-2 h-4 w-4 animate-spin' /> : null}
              {isEdit ? 'Save changes' : 'Create worker'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
