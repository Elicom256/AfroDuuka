import { Link, NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Users,
  PackageCheck,
  ShoppingBag,
  TrendingUp,
  Users2,
  Truck,
  DollarSign,
  Tag,
  Settings,
  BarChart3,
  AlertTriangle,
  History,
  Gift,
  Bell,
  MessageSquare,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';

const navSections = [
  {
    title: 'Overview',
    items: [{ label: 'Overview', to: '/admin', icon: LayoutDashboard }],
  },
  {
    title: 'Operations',
    items: [
      { label: 'Products', to: '/admin/products', icon: PackageCheck },
      { label: 'Sales', to: '/admin/sales', icon: DollarSign },
      { label: 'Purchases', to: '/admin/purchases', icon: Truck },
      { label: 'Orders', to: '/admin/orders', icon: ShoppingBag },
      { label: 'Inventory', to: '/admin/inventory', icon: AlertTriangle },
    ],
  },
  {
    title: 'People',
    items: [
      { label: 'Workers', to: '/admin/workers', icon: Users },
      { label: 'Customers', to: '/admin/customers', icon: Users2 },
    ],
  },
  {
    title: 'Business',
    items: [
      { label: 'Analytics', to: '/admin/analytics', icon: BarChart3 },
      { label: 'Reports', to: '/admin/reports', icon: TrendingUp },
      { label: 'Finances', to: '/admin/finances', icon: DollarSign },
      { label: 'Suppliers', to: '/admin/suppliers', icon: Truck },
      { label: 'Branches', to: '/admin/branches', icon: Truck },
    ],
  },
  {
    title: 'Marketing',
    items: [
      { label: 'Promotions', to: '/admin/promotions', icon: Gift },
      { label: 'Coupons', to: '/admin/coupons', icon: Tag },
    ],
  },
  {
    title: 'System',
    items: [
      { label: 'Notifications', to: '/admin/notifications', icon: Bell },
      { label: 'Messages', to: '/admin/messages', icon: MessageSquare },
      { label: 'History', to: '/admin/history', icon: History },
      { label: 'Settings', to: '/admin/settings', icon: Settings },
    ],
  },
];

type AdminSidebarProps = {
  onNavigate?: () => void;
};

export const AdminSidebar = ({ onNavigate }: AdminSidebarProps) => {
  const { data } = useLoggedinUserQuery();
  // console.log('data of user==>', data?.data);
  return (
    <nav className='flex flex-col h-full'>
      <div className='px-4 py-2 border-b border-border'>
        <h2 className='text-lg font-semibold tracking-tight'>Admin Panel</h2>
        <p className='text-xs text-muted-foreground mt-1'>Inventory • Shop</p>
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
          ))
        ) : (
          <Link to='create-business' className='hover:underline'>
            Add Business
          </Link>
        )}
      </div>

      {/* Optional Footer */}
      <div className='p-4 border-t border-border mt-auto'>{data && <UserProfile data={data} />}</div>
    </nav>
  );
};
