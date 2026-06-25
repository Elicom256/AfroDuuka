import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const PageShell = ({ title, description, children }: { title: string; description: string; children: ReactNode }) => (
  <Card className='rounded-3xl border border-border/70 bg-card p-6'>
    <CardHeader>
      <CardTitle>{title}</CardTitle>
      <CardDescription>{description}</CardDescription>
    </CardHeader>
    <CardContent className='space-y-4'>{children}</CardContent>
  </Card>
);

export const AdminInventoryPage = () => {
  const inventory = [
    { id: 'INV-001', product: 'Palm Oil', stock: 280, location: 'Warehouse A' },
    { id: 'INV-002', product: 'Maize Flour', stock: 120, location: 'Warehouse B' },
    { id: 'INV-003', product: 'Cleaning Supplies', stock: 54, location: 'Warehouse C' },
  ];

  return (
    <PageShell title='Inventory' description='Track product availability and warehouse stock levels.'>
      <div className='grid gap-4 md:grid-cols-3'>
        {inventory.map((item) => (
          <div key={item.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='text-sm font-medium text-muted-foreground'>{item.product}</p>
            <p className='mt-3 text-2xl font-semibold'>{item.stock}</p>
            <p className='mt-2 text-sm text-muted-foreground'>{item.location}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminCustomersPage = () => {
  const customers = [
    { name: 'Amina K.', phone: '+254 710 000 001', location: 'Nairobi' },
    { name: 'Juma O.', phone: '+254 720 000 002', location: 'Mombasa' },
    { name: 'Grace N.', phone: '+254 730 000 003', location: 'Kisumu' },
  ];

  return (
    <PageShell title='Customers' description='Review your customer base and contact information.'>
      <div className='space-y-4'>
        {customers.map((customer) => (
          <div key={customer.phone} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='font-semibold'>{customer.name}</p>
            <p className='text-sm text-muted-foreground'>{customer.phone}</p>
            <p className='text-sm text-muted-foreground'>{customer.location}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminAnalyticsPage = () => {
  const metrics = [
    { label: 'Sales this week', value: 'KSH 148,000' },
    { label: 'New customers', value: '68' },
    { label: 'Stock alerts', value: '4' },
  ];

  return (
    <PageShell title='Analytics' description='See the business trends and team performance at a glance.'>
      <div className='grid gap-4 md:grid-cols-3'>
        {metrics.map((metric) => (
          <div key={metric.label} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='text-sm text-muted-foreground'>{metric.label}</p>
            <p className='mt-3 text-2xl font-semibold'>{metric.value}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminReportsPage = () => {
  const reports = [
    { title: 'Weekly inventory review', status: 'Ready' },
    { title: 'Sales performance snapshot', status: 'Draft' },
    { title: 'Customer satisfaction summary', status: 'Ready' },
  ];

  return (
    <PageShell title='Reports' description='Browse recent business reports and export summaries.'>
      <div className='space-y-3'>
        {reports.map((report) => (
          <div key={report.title} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <div className='flex items-center justify-between gap-4'>
              <p className='font-semibold'>{report.title}</p>
              <span className='rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary'>
                {report.status}
              </span>
            </div>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminFinancesPage = () => {
  const finances = [
    { label: 'Revenue', value: 'KSH 480,000' },
    { label: 'Expenses', value: 'KSH 210,000' },
    { label: 'Profit', value: 'KSH 270,000' },
  ];

  return (
    <PageShell title='Finances' description='Review your current finances and available cash flow.'>
      <div className='grid gap-4 md:grid-cols-3'>
        {finances.map((item) => (
          <div key={item.label} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='text-sm text-muted-foreground'>{item.label}</p>
            <p className='mt-3 text-2xl font-semibold'>{item.value}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminSuppliersPage = () => {
  // return (
  //   <PageShell title='Suppliers' description='Manage supplier relationships and inventory partners.'>
  //     <div className='space-y-4'>
  //       {suppliers.map((supplier) => (
  //         <div key={supplier.name} className='rounded-3xl border border-border/70 bg-muted p-4'>
  //           <p className='font-semibold'>{supplier.name}</p>
  //           <p className='text-sm text-muted-foreground'>{supplier.product}</p>
  //         </div>
  //       ))}
  //     </div>
  //   </PageShell>
  // );
};

export const AdminPromotionsPage = () => {
  const promotions = [
    { title: 'Holiday bundle', expires: '3 days' },
    { title: 'Free delivery', expires: '1 week' },
  ];

  return (
    <PageShell title='Promotions' description='Create and review current marketing promotions.'>
      <div className='space-y-4'>
        {promotions.map((promo) => (
          <div key={promo.title} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='font-semibold'>{promo.title}</p>
            <p className='text-sm text-muted-foreground'>Expires in {promo.expires}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminCouponsPage = () => {
  const coupons = [
    { code: 'SAVE15', discount: '15%', status: 'Active' },
    { code: 'FREESHIP', discount: 'Free shipping', status: 'Active' },
  ];

  return (
    <PageShell title='Coupons' description='Track your coupon codes and current usage status.'>
      <div className='space-y-4'>
        {coupons.map((coupon) => (
          <div key={coupon.code} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='font-semibold'>{coupon.code}</p>
            <p className='text-sm text-muted-foreground'>{coupon.discount}</p>
            <p className='text-sm text-muted-foreground'>{coupon.status}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminHistoryPage = () => {
  const events = [
    { time: 'Today', description: 'Worker accounts updated' },
    { time: 'Yesterday', description: 'Inventory restocked' },
    { time: '2 days ago', description: 'New customer signups processed' },
  ];

  return (
    <PageShell title='History' description='Review recent system activity and business changes.'>
      <div className='space-y-4'>
        {events.map((event) => (
          <div key={event.time} className='rounded-3xl border border-border/70 bg-muted p-4'>
            <p className='text-sm text-muted-foreground'>{event.time}</p>
            <p className='mt-1 font-medium'>{event.description}</p>
          </div>
        ))}
      </div>
    </PageShell>
  );
};

export const AdminSettingsPage = () => (
  <PageShell title='Settings' description='Manage dashboard and account settings for the admin panel.'>
    <div className='space-y-4'>
      <div className='rounded-3xl border border-border/70 bg-muted p-4'>
        <p className='font-semibold'>Profile settings</p>
        <p className='text-sm text-muted-foreground'>Update admin profile or notification preferences.</p>
      </div>
      <div className='rounded-3xl border border-border/70 bg-muted p-4'>
        <p className='font-semibold'>Security settings</p>
        <p className='text-sm text-muted-foreground'>Manage session rules and access controls.</p>
      </div>
    </div>
  </PageShell>
);
