import { Pencil, SquarePen, UserRound } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { Link } from 'react-router-dom';

export const UserProfile = ({ user }: any) => {
  const { data } = useLoggedinUserQuery();
  //    console.log('data==>', data.data);
  return (
    <Dialog>
      <DialogTrigger asChild>
        <div className='flex items-center gap-3 px-4 py-3 rounded-2xl bg-muted/50 cursor-pointer'>
          <div className='w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center'>
            <UserRound />
          </div>
          <div className='text-sm'>
            <p className='font-medium'>{data.data.name}</p>
            <p className='text-xs text-muted-foreground'>Online</p>
          </div>
        </div>
      </DialogTrigger>

      <DialogContent className='sm:max-w-sm '>
        <DialogHeader className='flex items-center'>
          <DialogTitle>
            <UserRound />
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
          <DialogClose asChild>
            <Button variant='outline'>Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
