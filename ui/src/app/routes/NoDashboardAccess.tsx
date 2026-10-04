import { Link } from 'react-router-dom';
import { ShieldAlert, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Shown to a signed-in user whose role has no dashboard in this build.
 *
 * `editor`, `supplier` and `customer` are all seeded real roles with no
 * `/dashboard/*` tree, so before this existed they logged in successfully and were
 * then dumped on the marketing 404 by the catch-all — a dead end reached by a
 * legitimate login, with no indication that the account was fine and only the
 * navigation was unavailable.
 *
 * This is deliberately not a 404. Nothing is missing and nothing is wrong with the
 * URL: the person is authenticated, and the honest message is that this build has
 * nowhere to put their role. It also has to be different from NotFound because an
 * unknown public URL *is* a real 404 and must keep saying so.
 */
export const NoDashboardAccess = ({ role }: { role: string | null }) => {
  return (
    <div className='min-h-screen bg-linear-to-br from-background to-muted flex items-center justify-center p-4'>
      <div className='max-w-md w-full space-y-8 text-center'>
        <div className='flex justify-center'>
          <div className='bg-muted p-6 rounded-full'>
            <ShieldAlert className='h-16 w-16 text-muted-foreground' />
          </div>
        </div>

        <div className='space-y-2'>
          <h1 className='text-3xl font-bold text-foreground'>No dashboard for your role</h1>
          <p className='text-muted-foreground'>
            {role
              ? `Your account is signed in and working, but "${role}" does not have a dashboard in this version of DuukaFlow.`
              : 'Your account is signed in and working, but it has no role assigned, so there is no dashboard to open.'}
          </p>
        </div>

        <div className='bg-card border border-border rounded-lg p-6'>
          <p className='text-sm text-muted-foreground mb-4'>
            An administrator can grant your account a role that has one. In the meantime:
          </p>
          <div className='flex flex-col gap-3'>
            <Link to='/'>
              <Button variant='outline' className='w-full'>
                <Home className='h-4 w-4 mr-2' />
                Go to Home
              </Button>
            </Link>
            <Button variant='ghost' className='w-full' onClick={() => window.history.back()}>
              <ArrowLeft className='h-4 w-4 mr-2' />
              Go Back
            </Button>
          </div>
        </div>

        <div className='pt-4'>
          <p className='text-xs text-muted-foreground'>
            Signed in as {role ?? 'a user with no role'}. If you expected a dashboard here, contact your administrator.
          </p>
        </div>
      </div>
    </div>
  );
};