import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  AlertTriangle,
  Truck,
  DollarSign,
  BarChart3,
  PackageCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';

const navSections = [
  {
    title: 'Overview',
    items: [{ label: 'Overview', to: '/manager', icon: LayoutDashboard }],
  },
  {
    title: 'Operations',
    items: [
      { label: 'Products', to: '/manager/products', icon: PackageCheck },
      { label: 'Sales', to: '/manager/sales', icon: DollarSign },
      { label: 'Purchases', to: '/manager/purchases', icon: Truck },
      { label: 'Inventory', to: '/manager/inventory', icon: AlertTriangle },
    ],
  },
  {
    title: 'Performance',
    items: [
      { label: 'Analytics', to: '/manager/analytics', icon: BarChart3 },
    ],
  },
];

type ManagerSidebarProps = {
  onNavigate?: () => void;
};

export const ManagerSidebar = ({ onNavigate }: ManagerSidebarProps) => {
  const { data } = useLoggedinUserQuery();
  return (
    <nav className='flex flex-col h-full'>
      <div className='px-4 py-2 border-b border-border'>
        <h2 className='text-lg font-semibold tracking-tight'>Manager Panel</h2>
        <p className='text-xs text-muted-foreground mt-1'>Branch Management</p>
      </div>

      <div className='flex-1 overflow-y-auto p-3 space-y-8'>
        {data && data?.data.business ? (
          navSections.map((section) => (
            <div key={section.title} className='space-y-1'>
              <h4 className='px-4 text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-2'>
                {section.title}
              </h4>

              {section.items.map((item) => {
                const Icon = item.icon;
                return (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                        isActive
                          ? 'bg-accent text-accent-foreground'
                          : 'text-muted-foreground'
                      )
                    }
                  >
                    <Icon className='h-4 w-4' />
                    {item.label}
                  </NavLink>
                );
              })}
            </div>
          ))
        ) : (
          <div className='p-4 text-center text-muted-foreground'>
            <p>No business data available</p>
          </div>
        )}
      </div>

      <div className='border-t border-border p-4'>
        {data && <UserProfile data={data} />}
      </div>
    </nav>
  );
};