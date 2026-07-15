import { NavLink } from 'react-router-dom';
import { LayoutDashboard, DollarSign, AlertTriangle, TrendingUp, PackageCheck, Receipt } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';

const navSections = [
  {
    title: 'Overview',
    items: [{ label: 'Overview', to: '/staff', icon: LayoutDashboard }],
  },
  {
    title: 'Operations',
    items: [
      { label: 'Products', to: '/staff/products', icon: PackageCheck },
      { label: 'Sales', to: '/staff/sales', icon: DollarSign },
      { label: 'Receipts', to: '/staff/receipts', icon: Receipt },
      { label: 'Inventory', to: '/staff/inventory', icon: AlertTriangle },
    ],
  },
  {
    title: 'Sales Flow',
    items: [{ label: 'Sales Overview', to: '/staff/sales-overview', icon: TrendingUp }],
  },
];

type StaffSidebarProps = {
  onNavigate?: () => void;
};

export const StaffSidebar = ({ onNavigate }: StaffSidebarProps) => {
  const { data } = useLoggedinUserQuery();
  return (
    <nav className='flex flex-col h-full'>
      <div className='px-4 py-2 border-b border-border'>
        <h2 className='text-lg font-semibold tracking-tight'>Staff Panel</h2>
        <p className='text-xs text-muted-foreground mt-1'>Daily Operations</p>
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
                        isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground',
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

      <div className='border-t border-border p-4'>{data && <UserProfile data={data} />}</div>
    </nav>
  );
};
