import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { ThemeToggle } from '@/components/ThemeToggle';
import logo from '../../../public/afroduuka.png';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';

const navLinks = [
  { label: 'Home', to: '/' },
  { label: 'Pricing', to: '/pricing' },
  { label: 'About', to: '/about' },
  { label: 'Documentation', to: '/documentation' },
];

export const NavBar: React.FC = () => {
  const [open, setOpen] = useState(false);
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const businessName = data?.data?.business?.name ?? 'DuukaFlow';
  const businessLogo = data?.data?.business?.logo ?? logo;
  // Eloquent serialises the businessBranch() relation under its snake_case key, so
  // businessBranch is always undefined here and the badge could never render.
  const branchName = data?.data?.business_branch?.name;

  return (
    <header className='sticky top-0 z-50 border-b border-border/70 bg-background/90 shadow-sm backdrop-blur-xl'>
      <div className='container mx-auto flex items-center justify-between gap-4 px-4 py-4'>
        <Link
          to='/'
          className='group inline-flex items-center gap-3 text-lg font-semibold tracking-tight text-foreground'
        >
          <img src={businessLogo} alt='' className='h-14 w-auto object-contain drop-shadow-sm' />
          <span className='flex flex-col items-start leading-tight'>
            <span className='hidden md:inline'>{businessName}</span>
            {branchName && (
              <Badge variant='outline' className='mt-1 text-xs font-normal'>
                {branchName}
              </Badge>
            )}
          </span>
        </Link>

        <nav className='hidden items-center gap-2 md:flex'>
          {navLinks.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                cn(
                  'rounded-full px-3 py-2 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-primary/10 text-primary'
                    : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
                )
              }
            >
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div className='hidden items-center gap-4 md:flex'>
          <ThemeToggle compact />
          {data && role ? (
            <Link to='/dashboard'>Dashboard</Link>
          ) : (
            <>
              {/* Signing in and signing up are different decisions, so both are offered.
                  This used to be an either/or that only offered the trial, leaving a
                  returning customer with no way to reach the login screen. */}
              <Link
                to='/login'
                className='rounded-full px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted/70 hover:text-foreground'
              >
                Log in
              </Link>
              <Button asChild size='sm'>
                <Link to='/onboarding'>Start Free Trial</Link>
              </Button>
            </>
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
        <div className='border-t border-border/50 bg-background px-4 pb-4 md:hidden'>
          <div className='space-y-2'>
            {navLinks.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.to === '/'}
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  cn(
                    'block rounded-2xl px-4 py-3 text-sm font-medium transition-colors',
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
                  )
                }
              >
                {item.label}
              </NavLink>
            ))}
            <div className='flex items-center justify-between py-2'>
              <span className='text-sm text-muted-foreground'>Appearance</span>
              <ThemeToggle compact />
            </div>
            {/* Same pair as the desktop bar: log in, or start a trial. Offering only the
                trial here left mobile users with no route back to the login screen. */}
            {data && role ? (
              <Button asChild size='sm' className='w-full'>
                <Link to='/dashboard'>Dashboard</Link>
              </Button>
            ) : (
              <>
                <Link
                  to='/login'
                  onClick={() => setOpen(false)}
                  className='block rounded-2xl px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted/70 hover:text-foreground'
                >
                  Log in
                </Link>
                <Button asChild size='sm' className='mt-2 w-full'>
                  <Link to='/onboarding' onClick={() => setOpen(false)}>
                    Start Free Trial
                  </Link>
                </Button>
              </>
            )}
          </div>
        </div>
      ) : null}
    </header>
  );
};
