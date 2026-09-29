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
  Undo2,
  ArrowLeftToLine,
  Receipt,
  ShoppingCart,
  Package2,
  Wallet,
  Landmark,
  PieChart,
  ClipboardList,
  Calculator,
  Percent,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { UserProfile } from '../auth/UserProfile';
import { useGetUnreadCountQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { useFeatureSettings } from '@/app/hooks/useFeatureSettings';
import { getRolePrefix } from '@/lib/rolePrefix';

type BranchManagerSidebarProps = {
  onNavigate?: () => void;
};

const navSections: Array<{
  title: string;
  items: Array<{ label: string; to: string; icon: any; settingKey?: string }>;
}> = [
  {
    title: 'Dashboard',
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
    ],
  },
  {
    title: 'Tasks',
    items: [{ label: 'Todos', to: '/todos', icon: CheckSquare }],
  },
  {
    title: 'People',
    items: [
      { label: 'Workers', to: '/workers', icon: Users },
      { label: 'Suppliers', to: '/suppliers', icon: Users2, settingKey: 'suppliers' },
      { label: 'Customers', to: '/customers', icon: Users2, settingKey: 'customers' },
    ],
  },
  {
    title: 'Business',
    items: [
      { label: 'Analytics', to: '/analytics', icon: BarChart3 },
      { label: 'Reports', to: '/reports', icon: TrendingUp, settingKey: 'reports' },
      { label: 'Expenses', to: '/expenses', icon: Receipt },
      { label: 'Attendance', to: '/attendance', icon: AlertTriangle, settingKey: 'attendance' },
      { label: 'Payroll', to: '/remuneration', icon: Users },
      { label: 'Salaries', to: '/employee-salaries', icon: DollarSign },
    ],
  },
  {
    title: 'Financials',
    items: [
      { label: 'Cash Flow', to: '/cashflow', icon: Wallet },
      { label: 'Transactions', to: '/finance/transactions', icon: Landmark },
      { label: 'Reports', to: '/finance/reports', icon: PieChart },
    ],
  },
  {
    title: 'Marketing',
    items: [
      { label: 'Promotions', to: '/promotions', icon: Gift, settingKey: 'promotions' },
      { label: 'Coupons', to: '/coupons', icon: Tag },
    ],
  },
  {
    title: 'Integrations',
    items: [
      { label: 'Currency Rates', to: '/currency-rates', icon: Globe },
      { label: 'Printers', to: '/printers', icon: Printer },
    ],
  },
  {
    title: 'Inventory',
    items: [
      { label: 'Stock Transfers', to: '/stock-transfers', icon: ArrowLeftRight },
      { label: 'Reorder Rules', to: '/reorder-rules', icon: PackageSearch },
      { label: 'Report Exports', to: '/report-exports', icon: FileDown },
    ],
  },
  {
    title: 'Audits',
    items: [
      { label: 'Product Audits', to: '/product-audits', icon: ClipboardList },
      { label: 'Financial Audits', to: '/financial-audits', icon: Calculator },
    ],
  },
  {
    title: 'Taxes',
    items: [{ label: 'Tax Management', to: '/tax', icon: Percent }],
  },
  {
    title: 'System',
    items: [
      { label: 'Notifications', to: '/notifications', icon: Bell },
      { label: 'Messages', to: '/messages', icon: MessageSquare },
      { label: 'Activity Logs', to: '/activity-logs', icon: History },
    ],
  },
];

export const BranchManagerSidebar = ({ onNavigate }: BranchManagerSidebarProps) => {
  const { data: userData } = useLoggedinUserQuery();
  const { data: unreadData } = useGetUnreadCountQuery(undefined, { pollingInterval: 60000 });
  const features = useFeatureSettings();

  const role = userData?.data?.role?.name;
  const prefix = getRolePrefix(role);
  const unreadCount = unreadData?.unread_count ?? 0;

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
        <h2 className='text-lg font-semibold tracking-tight'>{role ?? 'Dashboard'}</h2>
        <p className='text-xs text-muted-foreground mt-1'>Branch Management</p>
      </div>

      <div className='flex-1 overflow-y-auto p-3 space-y-8'>
        {userData && userData?.data.business ? (
          filteredSections.map((section) => (
            <div key={section.title} className='space-y-1'>
              <h4 className='px-4 text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-2'>
                {section.title}
              </h4>

              {section.items.map((item) => {
                const Icon = item.icon;
                const isNotifications = item.to === '/notifications';
                const itemPath = `${prefix}${item.to}`;

                return (
                  <NavLink
                    key={item.to}
                    to={!role ? '/login' : itemPath}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 relative',
                        isActive && item.label.toLowerCase() !== 'overview'
                          ? 'bg-mutedd uppercase text-green-400 text-xs'
                          : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                      )
                    }
                  >
                    <Icon className='h-4 w-4' />
                    {item.label}

                    {isNotifications && unreadCount > 0 && (
                      <div className='ml-auto flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-medium bg-red-500 text-white rounded-full'>
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

      <div className='p-4 border-t border-border mt-auto'>{userData && <UserProfile data={userData} />}</div>
    </nav>
  );
};
