import './App.css';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { NavBar } from './app/navbar/NavBar';
import { AppRoutes } from './app/routes/AppRoutes';
import { Footer } from './app/pages/public/Footer';
import { Toaster } from '@/components/ui/sonner';
import { OfflineStatusIndicator } from '@/components/offline/OfflineStatusIndicator';
import { useSyncFallback } from '@/hooks/use-sync-engine';

/**
 * The dashboard-only offline surface.
 *
 * Bundling the fallback with the indicator keeps both mounted for exactly the routes
 * that have connectivity monitoring and queued writes (the `/dashboard/*` tree). A
 * component boundary lets {@link useSyncFallback} be gated without a conditional hook.
 */
function DashboardOffline() {
  useSyncFallback();
  return (
    <div className='pointer-events-none fixed bottom-4 left-4 z-40'>
      <OfflineStatusIndicator />
    </div>
  );
}

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
      <a
        href='#main-content'
        className='sr-only z-50 rounded-md bg-background px-4 py-2 text-foreground shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4'
      >
        Skip to main content
      </a>
      {!isDashboard && <NavBar />}
      <main id='main-content' tabIndex={-1} className='flex-1'>
        <AppRoutes />
      </main>
      {!isDashboard && <Footer />}
      {/*
        Live connectivity/sync status, kept as a floating pill so it never displaces the
        dashboard or POS layout (offline.md §6). Mounted once at the app root rather
        than per layout so every role — and the POS — gets it without touching each shell.
      */}
      {isDashboard && <DashboardOffline />}
      <Toaster position='top-right' />
    </div>
  );
}

export default App;
