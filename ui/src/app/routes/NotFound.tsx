import { Link } from 'react-router-dom';
import { AlertCircle, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

export const NotFound = () => {
  return (
    <div className='min-h-screen bg-linear-to-br from-background to-muted flex items-center justify-center p-4'>
      <div className='max-w-md w-full space-y-8 text-center'>
        <div className='flex justify-center'>
          <div className='bg-destructive/10 p-6 rounded-full'>
            <AlertCircle className='h-16 w-16 text-destructive' />
          </div>
        </div>

        <div className='space-y-2'>
          <h1 className='text-6xl font-bold text-foreground'>404</h1>
          <h2 className='text-2xl font-semibold text-foreground'>Page Not Found</h2>
          <p className='text-muted-foreground'>
            The page you're looking for doesn't exist or has been moved. It might have been removed or the URL may be
            incorrect.
          </p>
        </div>

        <div className='bg-card border border-border rounded-lg p-6'>
          <p className='text-sm text-muted-foreground mb-4'>Here are some helpful links to get you back on track:</p>
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
          <p className='text-xs text-muted-foreground'>If you believe this is an error, please contact support.</p>
        </div>
      </div>
    </div>
  );
};
