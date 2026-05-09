import { SquarePen, UserRound } from 'lucide-react';

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
import { useLogoutMutation } from '@/app/store/features/auth/authQuery';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

export const UserProfile = ({ data }: any) => {
  const [logout, { isLoading }] = useLogoutMutation();
  const handleLogout = async () => {
    try {
      const res = await logout().unwrap();
      console.log('res logout==>', res);
      if (res) {
        toast.success(res.message);
      }
      return (window.location.href = '/login');
    } catch (error) {
      console.log('error==>', error);
    }
  };
  return (
    <Dialog>
      <DialogTrigger asChild>
        <div className='flex items-center gap-3 px-4 py-3 rounded-2xl bg-muted/70 cursor-pointer'>
          <div className='w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center'>
            <UserRound />
          </div>
          <div className='text-sm'>
            <p className='font-medium'>{data.data.name}</p>
            <p className='text-xs text-green-400 text-center'>Online</p>
          </div>
        </div>
      </DialogTrigger>

      <DialogContent className='sm:max-w-sm '>
        <DialogHeader className='flex items-center'>
          <DialogTitle>
            <div className='w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center'>
              <UserRound />
            </div>
          </DialogTitle>
          <DialogDescription>{data.data.username}</DialogDescription>
        </DialogHeader>

        <div className='space-y-3 text-sm'>
          <div className='flex items-center gap-2'>
            <p className='text-muted-foreground'>Name</p>
            <p className='font-medium'>{data.data.name}</p>
          </div>

          <div className='flex items-center gap-2'>
            <p className='text-muted-foreground'>Username</p>
            <p className='font-medium'>{data.data.username}</p>
          </div>

          <div className='flex items-center gap-2'>
            <p className='text-muted-foreground'>Phone</p>
            <p className='font-medium'>{data.data.phone}</p>
          </div>
        </div>

        <DialogFooter className='flex items-center'>
          <Link to='/signup' className='bg-green-500 flex items-center gap-1 py-1 px-2 rounded-lg'>
            <span>Edit</span>
            <SquarePen />
          </Link>
          {/* <DialogClose asChild> */}
          <Button variant='outline' onClick={handleLogout}>
            {isLoading ? <LoadingState /> : 'Log out'}
          </Button>
          {/* </DialogClose> */}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
