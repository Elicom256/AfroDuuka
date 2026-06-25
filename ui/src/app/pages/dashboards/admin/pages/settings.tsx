import { useState } from 'react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
  Settings as SettingsIcon,
  CreditCard,
  UserPlus,
  Truck,
  BarChart,
  Gift,
  Clock,
  Sun,
  Moon,
  Monitor,
  Search,
  ChevronRight,
  Globe,
  Building2,
} from 'lucide-react';
import { useTheme } from 'next-themes';
import { Button } from '@/components/ui/button';
import { useEffect } from 'react';

/** Navigation link for settings sidebar */
const NavItem = ({ to, label, Icon, isActive }: { to: string; label: string; Icon: any; isActive?: boolean }) => (
  <Link
    to={to}
    className={`flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors ${
      isActive ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-accent/50 text-muted-foreground hover:text-foreground'
    }`}
  >
    <Icon className='h-5 w-5 shrink-0' />
    <span className='text-sm'>{label}</span>
    {isActive && <ChevronRight className='h-4 w-4 ml-auto' />}
  </Link>
);

const themeOptions = [
  { value: 'light', label: 'Light', Icon: Sun },
  { value: 'dark', label: 'Dark', Icon: Moon },
  { value: 'system', label: 'System', Icon: Monitor },
] as const;

const ThemeToggleCard = () => {
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  if (!mounted) return null; // Avoid hydration mismatch

  return (
    <Card className='border-border/70'>
      <CardHeader className='pb-3'>
        <CardTitle className='text-base flex items-center gap-2'>
          <Sun className='h-5 w-5' />
          Appearance
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className='flex gap-2'>
          {themeOptions.map(({ value, label, Icon: ThemeIcon }) => (
            <Button
              key={value}
              variant={theme === value ? 'default' : 'outline'}
              size='sm'
              onClick={() => setTheme(value)}
              className='flex-1 gap-2'
            >
              <ThemeIcon className='h-4 w-4' />
              {label}
            </Button>
          ))}
        </div>
      </CardContent>
    </Card>
  );
};

export const AdminSettingsPage = () => {
  const location = useLocation();
  const [searchQuery, setSearchQuery] = useState('');

  // Check if a sub-setting is active (we're on a specific settings page, not the index)
  const isOnSubPage = location.pathname !== '/admin/settings';

  const navItems = [
    { to: 'payment-settings', label: 'Payment Methods', Icon: CreditCard },
    { to: 'customer-settings', label: 'Customers', Icon: UserPlus },
    { to: 'supplier-settings', label: 'Suppliers', Icon: Truck },
    { to: 'reports-settings', label: 'Reports', Icon: BarChart },
    { to: 'promotions-settings', label: 'Promotions', Icon: Gift },
    { to: 'attendance-settings', label: 'Attendance', Icon: Clock },
    { to: 'business-info', label: 'Business Info', Icon: Building2 },
    { to: 'currency-settings', label: 'Currency', Icon: Globe },
  ];

  const filteredNav = searchQuery
    ? navItems.filter((item) => item.label.toLowerCase().includes(searchQuery.toLowerCase()))
    : navItems;

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <SettingsIcon className='h-8 w-8' />
          Settings
        </h1>
        <p className='text-muted-foreground mt-1'>Manage your business configuration, preferences, and appearance</p>
      </div>

      <div className='grid gap-6 lg:grid-cols-4'>
        {/* Sidebar */}
        <aside className='lg:col-span-1 space-y-4'>
          {/* Search */}
          <div className='relative'>
            <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
            <Input
              placeholder='Search settings...'
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className='pl-9'
            />
          </div>

          {/* Navigation */}
          <Card className='border-border/70'>
            <CardContent className='p-2 space-y-1'>
              {filteredNav.map((item) => {
                const isActive = location.pathname.endsWith(item.to);
                return <NavItem key={item.to} {...item} isActive={isActive} />;
              })}
            </CardContent>
          </Card>

          {/* Theme Toggle */}
          <ThemeToggleCard />
        </aside>

        {/* Main Content */}
        <main className='lg:col-span-3 min-h-[300px]'>
          {isOnSubPage ? (
            <Outlet />
          ) : (
            <div className='space-y-4'>
              <p className='text-muted-foreground text-sm'>Select a settings category from the sidebar to get started.</p>
              <div className='grid gap-3 sm:grid-cols-2'>
                {navItems.map((item) => (
                  <Link key={item.to} to={item.to}>
                    <Card className='border-border/70 transition-all hover:shadow-md hover:border-primary/30 cursor-pointer'>
                      <CardContent className='p-5 flex items-center gap-4'>
                        <div className='rounded-lg bg-primary/10 p-3'>
                          <item.Icon className='h-6 w-6 text-primary' />
                        </div>
                        <div>
                          <p className='font-medium'>{item.label}</p>
                          <p className='text-xs text-muted-foreground'>Configure {item.label.toLowerCase()}</p>
                        </div>
                      </CardContent>
                    </Card>
                  </Link>
                ))}
              </div>
            </div>
          )}
        </main>
      </div>
    </div>
  );
};

export default AdminSettingsPage;
