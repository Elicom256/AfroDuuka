import { NavLink } from 'react-router-dom';
import { LayoutDashboard, Users, PackageCheck, ShoppingBag } from 'lucide-react';
import { cn } from '@/lib/utils';

const navItems = [
  { label: 'Dashboard', to: '/admin', icon: LayoutDashboard },
  { label: 'Workers', to: '/admin/workers', icon: Users },
  { label: 'Products', to: '/admin/products', icon: PackageCheck },
  { label: 'Orders', to: '/admin/orders', icon: ShoppingBag },
];

type AdminSidebarProps = {
  onNavigate?: () => void;
};

export const AdminSidebar = ({ onNavigate }: AdminSidebarProps) => {
  return (
    <nav className='flex flex-col gap-2'>
      {navItems.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.to}
            to={item.to}
            onClick={onNavigate}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition',
                isActive
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
              )
            }
          >
            <Icon className='h-4 w-4' />
            {item.label}
          </NavLink>
        );
      })}
    </nav>
  );
};
