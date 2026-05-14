import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Menu } from 'lucide-react';
import { ManagerSidebar } from './ManagerSidebar';

export const ManagerLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className='min-h-screen bg-background text-foreground'>
      <div className='border-b border-border/70 bg-background/80 p-4 shadow-sm shadow-slate-950/5 backdrop-blur md:hidden'>
        <div className='container mx-auto flex items-center justify-between gap-4'>
          <div>
            <p className='text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground'>Manager</p>
            <h1 className='text-lg font-semibold'>DuukaFlow Dashboard</h1>
          </div>
          <Sheet open={sidebarOpen} onOpenChange={setSidebarOpen}>
            <SheetTrigger asChild>
              <Button variant='outline' size='sm' className='gap-2'>
                <Menu className='h-4 w-4' />
                Menu
              </Button>
            </SheetTrigger>
            <SheetContent side='left' className='max-w-xs'>
              <SheetHeader>
                <SheetTitle>Navigation</SheetTitle>
              </SheetHeader>
              <div className='mt-4'>
                <ManagerSidebar onNavigate={() => setSidebarOpen(false)} />
              </div>
            </SheetContent>
          </Sheet>
        </div>
      </div>

      <div className='md:grid md:grid-cols-[280px_minmax(0,1fr)]'>
        <aside className='hidden border-r border-border/70 bg-muted/50 p-6 md:flex md:flex-col md:sticky md:top-0 md:h-screen md:overflow-hidden'>
          <ManagerSidebar />
        </aside>

        <main className='flex min-h-screen flex-col p-6'>
          <div className='mt-6'>
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};
