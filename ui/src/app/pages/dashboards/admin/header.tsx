import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';

export const AdminHeader = () => {
  return (
    <div className='flex flex-col gap-4 rounded-3xl border border-border/70 bg-card p-6 shadow-sm shadow-slate-950/5 sm:flex-row sm:items-end sm:justify-between'>
      <div>
        <p className='text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground'>Admin console</p>
        <h2 className='mt-2 text-2xl font-semibold'>Manage your business operations</h2>
        <p className='mt-2 max-w-2xl text-sm text-muted-foreground'>
          View workers, manage roles, and keep the dashboard in sync with your RTK Query backend.
        </p>
      </div>

      <div className='flex flex-wrap gap-2'>
        <Button asChild size='sm' variant='outline'>
          <Link to='/admin/workers'>Workers</Link>
        </Button>
        <Button asChild size='sm'>
          <Link to='/admin/products'>Products</Link>
        </Button>
      </div>
    </div>
  );
};
