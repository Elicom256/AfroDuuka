import './App.css';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { NavBar } from './app/navbar/NavBar';
import { AppRoutes } from './app/routes/AppRoutes';
import { Footer } from './app/pages/public/Footer';
import { Toaster } from '@/components/ui/sonner';

function App() {
  const location = useLocation();
  // Every role mounts under /dashboard/* (see AppRoutes), so that prefix is the only
  // signal needed. The old per-role prefixes (/executive, /branchmanager, /coresupport,
  // …) no longer have routes and never matched. Compared on the segment boundary so a
  // URL like /dashboard-notes is not mistaken for the dashboard.
  const { pathname } = location;
  const isDashboard = pathname === '/dashboard' || pathname.startsWith('/dashboard/');

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
  }, [pathname]);

  return (
    <div className='flex min-h-screen flex-col bg-background text-foreground'>
      {!isDashboard && <NavBar />}
      <main className='flex-1'>
        <AppRoutes />
      </main>
      {!isDashboard && <Footer />}
      <Toaster position='top-right' />
    </div>
  );
}

export default App;
