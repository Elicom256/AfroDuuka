import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { ExecutiveSidebar } from './ExecutiveSidebar';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { DashboardTopBar } from '../shared/DashboardTopBar';

export const ExecutiveLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name ?? 'Dashboard';

  return (
    <Sheet open={sidebarOpen} onOpenChange={setSidebarOpen}>
      <div className='min-h-screen bg-background text-foreground'>
        <DashboardTopBar role={role} userData={data} onMenuClick={() => setSidebarOpen(true)} />
        <SheetContent side='left' className='max-w-xs p-0'>
          <SheetHeader className='sr-only'>
            <SheetTitle>Navigation</SheetTitle>
          </SheetHeader>
          <ExecutiveSidebar onNavigate={() => setSidebarOpen(false)} />
        </SheetContent>

        <div className='md:grid md:grid-cols-[260px_minmax(0,1fr)]'>
          <aside className='sticky top-16 hidden h-[calc(100vh-4rem)] border-r border-border/70 bg-sidebar text-sidebar-foreground md:flex md:flex-col md:overflow-hidden'>
            <ExecutiveSidebar />
          </aside>

          <main className='min-h-[calc(100vh-4rem)] min-w-0 p-4 sm:p-6'>
            <Outlet />
          </main>
        </div>
      </div>
    </Sheet>
  );
};
