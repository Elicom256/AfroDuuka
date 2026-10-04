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
import { clearToken } from '@/lib/session';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { LoadingState } from '@/utils/LoadingState';

type ProfileData = {
  data?: {
    name?: string;
    username?: string;
    email?: string;
    phone?: string;
  };
};

export const UserProfile = ({ data, compact = false }: { data: ProfileData; compact?: boolean }) => {
  const [logout, { isLoading }] = useLogoutMutation();
  const handleLogout = async () => {
    try {
      const res = await logout().unwrap();
      if (res) {
        toast.success(res.message);
      }
    } catch {
      // Deliberately not fatal, and deliberately not the end of the flow either.
      // The server may already have revoked this token, or the request may have
      // failed on a network that is down. Either way the person asked to be signed
      // out, and leaving a token in localStorage because the confirmation call
      // failed is how you end up unable to browse your own marketing site.
      toast.error('Could not reach the server, but you have been signed out locally.');
    } finally {
      // Must happen even when the request above failed. This omission was the whole
      // bug: the server revoked the token, localStorage kept it, and every public
      // page then redirected to /login forever because the app had no way to tell a
      // logged-out visitor from a stale-token one.
      clearToken();
      window.location.href = '/login';
    }
  };
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button
          type='button'
          variant={compact ? 'ghost' : 'outline'}
          size={compact ? 'icon' : 'default'}
          className={compact ? 'h-9 w-9 rounded-full border border-border/70' : 'gap-3 px-3'}
          aria-label='Open profile and account actions'
          title='Profile and account'
        >
          {compact ? (
            <UserRound className='h-4 w-4' />
          ) : (
            <>
              <span className='grid h-8 w-8 place-items-center rounded-full bg-primary/15 text-primary'>
                <UserRound className='h-4 w-4' />
              </span>
              <span className='text-left'>
                <span className='block text-sm font-medium'>{data?.data?.name ?? data?.data?.username}</span>
                <span className='block text-xs text-muted-foreground'>Account</span>
              </span>
            </>
          )}
        </Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-sm '>
        <DialogHeader className='flex items-center'>
          <DialogTitle>
            <div className='mx-auto grid h-10 w-10 place-items-center rounded-full bg-primary/15 text-primary'>
              <UserRound />
            </div>
          </DialogTitle>
          <DialogDescription>{data?.data?.username ?? 'Account'}</DialogDescription>
        </DialogHeader>

        <div className='space-y-2 text-sm'>
          <p className='font-medium'>{data?.data?.name ?? data?.data?.username ?? 'User'}</p>
          {data?.data?.email && <p className='text-muted-foreground'>{data.data.email}</p>}
          {data?.data?.phone && <p className='text-muted-foreground'>{data.data.phone}</p>}
        </div>

        <DialogFooter className='flex items-center'>
          <Link
            to='/signup'
            className='inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm text-primary hover:bg-primary/10'
          >
            <span>Edit</span>
            <SquarePen className='h-4 w-4' />
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
