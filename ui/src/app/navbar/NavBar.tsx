import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import logo from '../../../public/logo-1.png';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
const navLinks = [
  { label: 'Home', to: '/' },
  { label: 'About', to: '/about' },
  { label: 'Documentation', to: '/documentation' },
  // { label: 'Admin', to: '/admin' },
];

export const NavBar: React.FC = () => {
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const { data } = useLoggedinUserQuery();

  return (
    <header className='sticky top-0 z-50 border-b border-border/70 bg-slate-950/90 backdrop-blur-xl shadow-sm shadow-slate-950/40'>
      <div className='container mx-auto flex items-center justify-between gap-4 px-4 py-4'>
        <Link
          to='/'
          className='group inline-flex items-center gap-3 text-lg font-semibold tracking-tight text-foreground'
        >
          <img src={logo} alt='' className='h-14 w-auto object-contain drop-shadow-sm' />
          {/* <Sparkles className='h-6 w-6 text-primary' /> */}
          <span>DuukaFlow</span>
        </Link>

        <nav className='hidden items-center gap-2 md:flex'>
          {navLinks.map((item) => {
            const active = location.pathname === item.to || location.pathname.startsWith(item.to + '/');
            return (
              <Link
                key={item.to}
                to={item.to}
                className={cn(
                  'rounded-full px-3 py-2 text-sm font-medium transition-colors',
                  active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground',
                )}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className='hidden items-center gap-2 md:flex'>
          {data ? (
            <Link to='/admin'>Dashboard</Link>
          ) : (
            <Button asChild size='sm'>
              <Link to='/login'>Try It</Link>
            </Button>
          )}
        </div>

        <button
          type='button'
          aria-label='Toggle navigation'
          className='inline-flex h-10 w-10 items-center justify-center rounded-full border border-border/70 text-foreground transition-colors hover:bg-muted/60 md:hidden'
          onClick={() => setOpen((current) => !current)}
        >
          {open ? <X className='h-5 w-5' /> : <Menu className='h-5 w-5' />}
        </button>
      </div>

      {open ? (
        <div className='border-t border-border/50 bg-slate-950/95 px-4 pb-4 md:hidden'>
          <div className='space-y-2'>
            {navLinks.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                onClick={() => setOpen(false)}
                className={cn(
                  'block rounded-2xl px-4 py-3 text-sm font-medium transition-colors',
                  location.pathname === item.to
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
                )}
              >
                {item.label}
              </Link>
            ))}
            <Button asChild size='sm' className='w-full'>
              <Link to='/documentation'>Get started</Link>
            </Button>
          </div>
        </div>
      ) : null}
    </header>
  );
};
