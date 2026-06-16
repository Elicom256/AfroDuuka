import { NavLink } from 'react-router-dom';
import { Wallet, Settings } from 'lucide-react';
import { cn } from '@/lib/utils';

const navItems = [
  { label: 'Payment Gateways', to: '/superadmin/payment-gateways', icon: Wallet },
  { label: 'Settings', to: '/superadmin/settings', icon: Settings },
];

type SuperAdminSidebarProps = {
  onNavigate?: () => void;
};

export const SuperAdminSidebar = ({ onNavigate }: SuperAdminSidebarProps) => (
  <nav className='flex flex-col h-full'>
    <div className='px-4 py-2 border-b border-border'>
      <h2 className='text-lg font-semibold tracking-tight'>Super Admin</h2>
      <p className='text-xs text-muted-foreground mt-1'>System Administration</p>
    </div>
    <div className='flex-1 overflow-y-auto p-3 space-y-1'>
      {navItems.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.to}
            to={item.to}
            onClick={onNavigate}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200',
                isActive
                  ? 'bg-primary text-primary-foreground shadow-sm'
                  : 'text-muted-foreground hover:bg-muted hover:text-foreground',
              )
            }
          >
            <Icon className='h-4 w-4' />
            {item.label}
          </NavLink>
        );
      })}
    </div>
  </nav>
);
