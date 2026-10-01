import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  PackageSearch,
  ClipboardList,
  Truck,
  History,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { DASHBOARD_PREFIX } from '@/lib/rolePrefix';

const navItems = [
  { label: 'Overview', to: '/', icon: LayoutDashboard, end: true },
  { label: 'Reorder Suggestions', to: '/reorder-suggestions', icon: PackageSearch },
  { label: 'Purchase Orders', to: '/purchase-orders', icon: ClipboardList },
  { label: 'Suppliers', to: '/suppliers', icon: Truck },
  { label: 'History', to: '/history', icon: History },
];

type ProcurementSidebarProps = {
  onNavigate?: () => void;
};

export const ProcurementSidebar = ({ onNavigate }: ProcurementSidebarProps) => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const prefix = DASHBOARD_PREFIX;

  return (
    <nav className='flex flex-col h-full'>
      <div className='px-4 py-2 border-b border-border'>
        <h2 className='text-lg font-semibold tracking-tight'>Procurement</h2>
        <p className='text-xs text-muted-foreground mt-1'>Orders • Stock • Suppliers</p>
      </div>

      <div className='flex-1 overflow-y-auto p-3 space-y-1'>
        {navItems.map((item) => {
          const Icon = item.icon;
          const itemPath = `${prefix}${item.to}`;

          return (
            <NavLink
              key={item.to}
              to={!role ? '/login' : itemPath}
              end={item.end}
              onClick={onNavigate}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200',
                  isActive
                    ? 'bg-primary/10 text-primary'
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
};
