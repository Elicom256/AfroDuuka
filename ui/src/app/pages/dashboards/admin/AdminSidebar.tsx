import { Link, NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Users,
  PackageCheck,
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
  Globe,
  Printer,
  ArrowLeftRight,
  PackageSearch,
  FileText,
  Award,
  FileDown,
  CheckSquare,
  Crown,
  CreditCard,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';
import { useGetNotificationsQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { useFeatureSettings } from '@/app/hooks/useFeatureSettings';

type AdminSidebarProps = {
  onNavigate?: () => void;
};

const navSections: Array<{
  title: string;
  items: Array<{ label: string; to: string; icon: any; settingKey?: string }>;
}> = [
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
    ],
  },
  {
    title: 'Tasks',
    items: [
      { label: 'Todos', to: '/admin/todos', icon: CheckSquare },
    ],
  },
  {
    title: 'People',
    items: [
      { label: 'Workers', to: '/admin/workers', icon: Users },
      { label: 'Suppliers', to: '/admin/suppliers', icon: Users2, settingKey: 'suppliers' },
      { label: 'Customers', to: '/admin/customers', icon: Users2, settingKey: 'customers' },
    ],
  },
  {
    title: 'Subscription',
    items: [
      { label: 'Subscriptions', to: '/admin/subscriptions', icon: Crown },
    ],
  },
  {
    title: 'Business',
    items: [
      { label: 'Analytics', to: '/admin/analytics', icon: BarChart3 },
      { label: 'Reports', to: '/admin/reports', icon: TrendingUp, settingKey: 'reports' },
      { label: 'Finances', to: '/admin/finances', icon: DollarSign },
      { label: 'Attendance', to: '/admin/attendance', icon: AlertTriangle, settingKey: 'attendance' },
      { label: 'Taxes', to: '/admin/taxes', icon: DollarSign },
      { label: 'Payroll', to: '/admin/remuneration', icon: Users },
      { label: 'Salaries', to: '/admin/employee-salaries', icon: DollarSign },
      { label: 'Activity Logs', to: '/admin/activity-logs', icon: History },
      { label: 'Branches', to: '/admin/branches', icon: Truck },
    ],
  },
  {
    title: 'Marketing',
    items: [
      { label: 'Promotions', to: '/admin/promotions', icon: Gift, settingKey: 'promotions' },
      { label: 'Coupons', to: '/admin/coupons', icon: Tag },
      { label: 'Loyalty', to: '/admin/loyalty', icon: Award },
    ],
  },
  {
    title: 'Integrations',
    items: [
      { label: 'Currency Rates', to: '/admin/currency-rates', icon: Globe },
      { label: 'Printers', to: '/admin/printers', icon: Printer },
      { label: 'Tax Invoices', to: '/admin/tax-invoices', icon: FileText },
    ],
  },
  {
    title: 'Inventory',
    items: [
      { label: 'Stock Transfers', to: '/admin/stock-transfers', icon: ArrowLeftRight },
      { label: 'Reorder Rules', to: '/admin/reorder-rules', icon: PackageSearch },
      { label: 'Report Exports', to: '/admin/report-exports', icon: FileDown },
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

export const AdminSidebar = ({ onNavigate }: AdminSidebarProps) => {
  const { data: userData } = useLoggedinUserQuery();
  const { data: notificationsData } = useGetNotificationsQuery(undefined, { pollingInterval: 720000 });
  const features = useFeatureSettings();

  const role = userData?.data?.role?.name;
  const notifications = notificationsData?.notifications || [];
  const unreadCount = notifications.filter((n: any) => !n.is_read).length;

  // Filter nav sections based on feature settings
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
        <h2 className='text-lg font-semibold tracking-tight'>Admin Panel</h2>
        <p className='text-xs text-muted-foreground mt-1'>Inventory • Shop</p>
      </div>

      <div className='flex-1 overflow-y-auto p-3 space-y-8'>
        {userData && userData?.data?.business ? (
          filteredSections.map((section) => (
            <div key={section.title} className='space-y-1'>
              <h4 className='px-4 text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-2'>
                {section.title}
              </h4>

              {section.items.map((item) => {
                const Icon = item.icon;
                const isNotifications = item.to === '/admin/notifications';

                return (
                  <NavLink
                    key={item.to}
                    to={!role || role !== 'admin' ? '/login' : item.to}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 relative',
                        isActive
                          ? 'bg-primary text-primary-foreground shadow-sm'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                      )
                    }
                  >
                    <Icon className='h-4 w-4' />
                    {item.label}

                    {/* Unread Badge - Only for Notifications */}
                    {isNotifications && unreadCount > 0 && (
                      <div className='ml-auto flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-medium bg-red-500 text-white rounded-full'>
                        {unreadCount > 99 ? '99+' : unreadCount}
                      </div>
                    )}
                  </NavLink>
                );
              })}
            </div>
          ))
        ) : (
          <Link to='/create-business' className='hover:underline'>
            Add Business
          </Link>
        )}
      </div>

      {/* Footer */}
      <div className='p-4 border-t border-border mt-auto'>{userData && <UserProfile data={userData} />}</div>
    </nav>
  );
};
