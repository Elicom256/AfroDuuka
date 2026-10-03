import { Link, NavLink } from 'react-router-dom';
import {
  Activity,
  AlertTriangle,
  ArrowLeftToLine,
  ArrowLeftRight,
  BarChart3,
  Bell,
  Calculator,
  CheckSquare,
  ClipboardList,
  DollarSign,
  FileDown,
  FileText,
  Gift,
  Globe,
  Landmark,
  LayoutDashboard,
  MessageSquare,
  Package2,
  PackageCheck,
  PackageSearch,
  Percent,
  PieChart,
  Printer,
  Receipt,
  ShoppingCart,
  Tag,
  TrendingUp,
  Truck,
  Undo2,
  Users,
  Users2,
  Wallet,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { useGetUnreadCountQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { useFeatureSettings } from '@/app/hooks/useFeatureSettings';

type BranchManagerSidebarProps = {
  onNavigate?: () => void;
};

type NavigationItem = { label: string; to: string; icon: typeof LayoutDashboard; settingKey?: string };
type NavigationSection = { title: string; items: NavigationItem[] };

const navSections: NavigationSection[] = [
  { title: 'Dashboard', items: [{ label: 'Overview', to: '/', icon: LayoutDashboard }] },
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
  { title: 'Tasks', items: [{ label: 'Todos', to: '/todos', icon: CheckSquare }] },
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
  { title: 'Taxes', items: [{ label: 'Tax Management', to: '/tax', icon: Percent }] },
  {
    title: 'System',
    items: [
      { label: 'Notifications', to: '/notifications', icon: Bell },
      { label: 'Messages', to: '/messages', icon: MessageSquare },
      { label: 'My Activity', to: '/activity-log', icon: Activity },
    ],
  },
];

export const BranchManagerSidebar = ({ onNavigate }: BranchManagerSidebarProps) => {
  const { data: userData } = useLoggedinUserQuery();
  const { data: unreadData } = useGetUnreadCountQuery(undefined, { pollingInterval: 60000 });
  const features = useFeatureSettings();
  const role = userData?.data?.role?.name;
  const prefix = '/dashboard';
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
    <nav className='flex h-full flex-col'>
      <div className='flex-1 overflow-y-auto p-3'>
        {userData?.data?.business ? (
          <div className='space-y-5'>
            {filteredSections.map((section) => (
              <div key={section.title} className='space-y-1'>
                <h4 className='mb-1 px-3 text-[10px] font-medium uppercase tracking-wider text-muted-foreground'>
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
                          'relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                          isActive
                            ? 'bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )
                      }
                    >
                      <Icon className='h-4 w-4' />
                      {item.label}
                      {item.to === '/notifications' && unreadCount > 0 && (
                        <span className='ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1.5 text-xs font-medium text-destructive-foreground'>
                          {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                      )}
                    </NavLink>
                  );
                })}
              </div>
            ))}
          </div>
        ) : (
          <Link to='/create-business' className='hover:underline'>
            Add Business
          </Link>
        )}
      </div>
    </nav>
  );
};
