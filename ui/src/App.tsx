import './App.css';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { NavBar } from './app/navbar/NavBar';
import { AppRoutes } from './app/routes/AppRoutes';
import { Footer } from './app/pages/public/Footer';
import { Toaster } from '@/components/ui/sonner';

function App() {
  const location = useLocation();
  const hideFooter = location.pathname.startsWith('/admin');

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
  }, [location.pathname]);

  return (
    <div className='flex min-h-screen flex-col bg-background text-foreground'>
      {!hideFooter && <NavBar />}
      <main className='flex-1'>
        <AppRoutes />
      </main>
      {!hideFooter && <Footer />}
      <Toaster position='top-right' />
    </div>
  );
}

export default App;
