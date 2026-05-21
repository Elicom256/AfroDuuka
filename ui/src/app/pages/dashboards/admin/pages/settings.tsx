import { Link, Outlet } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Settings as SettingsIcon, CreditCard, UserPlus, Truck, BarChart, Gift, Clock } from 'lucide-react';

const NavItem = ({ to, label, Icon }: { to: string; label: string; Icon: any }) => (
  <Link to={to} className='flex items-center gap-3 rounded-lg p-3 hover:bg-accent/50'>
    <Icon className='h-5 w-5' />
    <span className='font-medium'>{label}</span>
  </Link>
);

export const AdminSettingsPage = () => {
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Settings</CardTitle>
      </CardHeader>
      <CardContent className='grid gap-6 md:grid-cols-4'>
        <aside className='col-span-1 space-y-2'>
          <NavItem to='payment-settings' label='Payment Methods' Icon={CreditCard} />
          <NavItem to='customer-settings' label='Customers' Icon={UserPlus} />
          <NavItem to='supplier-settings' label='Suppliers' Icon={Truck} />
          <NavItem to='reports-settings' label='Reports' Icon={BarChart} />
          <NavItem to='promotions-settings' label='Promotions' Icon={Gift} />
          <NavItem to='attendance-settings' label='Attendance' Icon={Clock} />
        </aside>

        <main className='col-span-3'>
          <Outlet />
        </main>
      </CardContent>
    </Card>
  );
};

export default AdminSettingsPage;
