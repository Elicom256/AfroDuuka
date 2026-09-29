import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  AlertTriangle,
  Truck,
  DollarSign,
  BarChart3,
  PackageCheck,
  Users,
  Users2,
  TrendingUp,
  Gift,
  CalendarCheck,
  Bell,
  MessageSquare,
  Undo2,
  FileText,
  ArrowLeftToLine,
  ShoppingCart,
  Package2,
  Wallet,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';
import { useFeatureSettings } from '@/app/hooks/useFeatureSettings';
import { getRolePrefix } from '@/lib/rolePrefix';

const navSections: Array<{
  title: string;
  items: Array<{ label: string; to: string; icon: any; settingKey?: string }>;
}> = [
  {
    title: 'Overview',
    items: [{ label: 'Overview', to: '/', icon: LayoutDashboard }],
  },
  {
    title: 'Operations',
    items: [
      { label: 'POS', to: '/pos', icon: ShoppingCart },
      { label: 'Products', to: '/products', icon: PackageCheck },
      { label: 'Sales', to: '/sales', icon: DollarSign },
      { label: 'Receipts', to: '/receipts', icon: FileText },
      { label: 'Purchases', to: '/purchases', icon: Truck },
      { label: 'Sale Returns', to: '/sale-returns', icon: Undo2 },
      { label: 'Purchase Returns', to: '/purchase-returns', icon: ArrowLeftToLine },
      { label: 'Orders', to: '/orders', icon: Package2 },
      { label: 'Quotations', to: '/quotations', icon: FileText },
      { label: 'Inventory', to: '/inventory', icon: AlertTriangle },
      { label: 'Workers', to: '/workers', icon: Users },
      { label: 'Customers', to: '/customers', icon: Users2, settingKey: 'customers' },
      { label: 'Suppliers', to: '/suppliers', icon: Truck, settingKey: 'suppliers' },
    ],
  },
  {
    title: 'Financials',
    items: [
      { label: 'Dashboard', to: '/finance', icon: Wallet },
    ],
  },
  {
    title: 'Performance',
    items: [
      { label: 'Analytics', to: '/analytics', icon: BarChart3 },
      { label: 'Reports', to: '/reports', icon: TrendingUp, settingKey: 'reports' },
    ],
  },
  {
    title: 'System',
    items: [
      { label: 'Notifications', to: '/notifications', icon: Bell },
      { label: 'Messages', to: '/messages', icon: MessageSquare },
      { label: 'Promotions', to: '/promotions', icon: Gift, settingKey: 'promotions' },
      { label: 'Attendance', to: '/attendance', icon: CalendarCheck, settingKey: 'attendance' },
    ],
  },
];

type OperationsSidebarProps = {
  onNavigate?: () => void;
};

export const OperationsSidebar = ({ onNavigate }: OperationsSidebarProps) => {
  const { data } = useLoggedinUserQuery();
  const features = useFeatureSettings();

  const role = data?.data?.role?.name;
  const prefix = getRolePrefix(role);

  const filteredSections = navSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => {
        if (item.settingKey && !features[item.settingKey as keyof typeof features]) return false;
        return true;
      }),
    }))
    .filter((section) => section.items.length > 0);

  return (
    <nav className='flex flex-col h-full'>
      <div className='px-4 py-2 border-b border-border'>
        <h2 className='text-lg font-semibold tracking-tight'>Operations Panel</h2>
        <p className='text-xs text-muted-foreground mt-1'>Branch Management</p>
      </div>

      <div className='flex-1 overflow-y-auto p-3 space-y-8'>
        {data && data?.data.business ? (
          filteredSections.map((section) => (
            <div key={section.title} className='space-y-1'>
              <h4 className='px-4 text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-2'>
                {section.title}
              </h4>

              {section.items.map((item) => {
                const Icon = item.icon;
                const itemPath = `${prefix}${item.to}`;

                return (
                  <NavLink
                    key={item.to}
                    to={!role ? '/login' : itemPath}
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
