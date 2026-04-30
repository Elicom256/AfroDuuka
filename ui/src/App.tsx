import './App.css';
import { NavBar } from './app/navbar/NavBar';
import { AppRoutes } from './app/routes/AppRoutes';
import { Footer } from './app/pages/public/Footer';

function App() {
  return (
    <div className='flex min-h-screen flex-col bg-background text-foreground'>
      <NavBar />
      <main className='flex-1'>
        <AppRoutes />
      </main>
      <Footer />
    </div>
  );
}

export default App;
