import './App.css';
import { NavBar } from './app/navbar/NavBar';
import { AppRoutes } from './app/routes/AppRoutes';
import { Footer } from './app/pages/public/Footer';
import { Toaster } from '@/components/ui/sonner';

function App() {
  return (
    <div className='flex min-h-screen flex-col bg-slate-950 text-foreground'>
      <NavBar />
      <main className='flex-1'>
        <AppRoutes />
      </main>
      <Footer />
      <Toaster />
    </div>
  );
}

export default App;
