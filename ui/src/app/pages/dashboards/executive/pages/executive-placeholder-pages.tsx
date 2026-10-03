import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { useBranchPromotionsQuery } from '@/app/store/features/branch/promotions/promotionsQuery';
import { useProductAnalyticsQuery, useProductRestockingQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useGetCashFlowsQuery } from '@/app/store/features/business/executive/cashFlowQuery';
import { useBranchReportsQuery, useLowStockQuery, useOutOfStockQuery } from '@/app/store/features/branch/reports/branchReportsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

const PageShell = ({ title, description, children }: { title: string; description: string; children: ReactNode }) => (
  <Card className='rounded-3xl border border-border/70 bg-card p-6'>
    <CardHeader>
      <CardTitle>{title}</CardTitle>
      <CardDescription>{description}</CardDescription>
    </CardHeader>
    <CardContent className='space-y-4'>{children}</CardContent>
  </Card>
);

const CardSkeleton = () => (
  <div className='space-y-3'>
    <Skeleton className='h-4 w-32' />
    <Skeleton className='h-4 w-full' />
    <Skeleton className='h-4 w-3/4' />
  </div>
);

export const ExecutiveCustomersPage = () => {
  const { data, isLoading } = useCustomersQuery();

  if (isLoading) return <PageShell title='Customers' description='Review your customer base and contact information.'><CardSkeleton /></PageShell>;

  const customers = data?.customers ?? data ?? [];

  return (
    <PageShell title='Customers' description='Review your customer base and contact information.'>
      <div className='space-y-4'>
        {customers.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No customers yet.</p>
        ) : (
          customers.slice(0, 10).map((customer: any) => (
            <div key={customer.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
              <p className='font-semibold'>{customer.name}</p>
              <p className='text-sm text-muted-foreground'>{customer.phone}</p>
              <p className='text-sm text-muted-foreground'>{customer.email}</p>
            </div>
          ))
        )}
      </div>
    </PageShell>
  );
};

export const ExecutiveAnalyticsPage = () => {
  const { data: analytics, isLoading: analyticsLoading } = useProductAnalyticsQuery();
  const { data: restocking, isLoading: restockingLoading } = useProductRestockingQuery();
  const { isLoading: lowLoading } = useLowStockQuery('30');
  const { data: outOfStock, isLoading: outLoading } = useOutOfStockQuery('30');

  const isLoading = analyticsLoading || restockingLoading || lowLoading || outLoading;

  if (isLoading) return <PageShell title='Analytics' description='See the business trends and team performance at a glance.'><CardSkeleton /></PageShell>;

  const totalProducts = analytics?.data?.total_products ?? 0;
  const lowStockCount = restocking?.data?.low_stock_count ?? 0;
  const atRiskCount = restocking?.data?.at_risk_count ?? 0;
  const outOfStockCount = (outOfStock?.data ?? []).length;

  const metrics = [
    { label: 'Total Products', value: String(totalProducts) },
    { label: 'Low Stock Items', value: String(lowStockCount) },
    { label: 'Out of Stock', value: String(outOfStockCount) },
    { label: 'At Risk', value: String(atRiskCount) },
  ];

  return (
    <PageShell title='Analytics' description='See the business trends and team performance at a glance.'>
      <div className='grid gap-4 md:grid-cols-2 xl:grid-cols-4'>
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

export const ExecutiveReportsPage = () => {
  const { data, isLoading } = useBranchReportsQuery();

  if (isLoading) return <PageShell title='Reports' description='Browse recent business reports and export summaries.'><CardSkeleton /></PageShell>;

  const reports = data?.reports ?? data ?? [];

  return (
    <PageShell title='Reports' description='Browse recent business reports and export summaries.'>
      <div className='space-y-3'>
        {reports.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No reports available.</p>
        ) : (
          reports.slice(0, 10).map((report: any) => (
            <div key={report.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
              <div className='flex items-center justify-between gap-4'>
                <p className='font-semibold'>{report.title ?? report.name}</p>
                <Badge variant='secondary'>{report.status ?? 'Ready'}</Badge>
              </div>
            </div>
          ))
        )}
      </div>
    </PageShell>
  );
};

export const ExecutiveFinancesPage = () => {
  const { currency } = useCurrency();
  const { data, isLoading } = useGetCashFlowsQuery();

  if (isLoading) return <PageShell title='Finances' description='Review your current finances and available cash flow.'><CardSkeleton /></PageShell>;

  const transactions = data?.data ?? [];
  const revenue = transactions
    .filter((t: any) => ['sale', 'payment_in'].includes(t.type))
    .reduce((sum: number, t: any) => sum + Number(t.amount ?? 0), 0);
  const expenses = transactions
    .filter((t: any) => ['purchase', 'expense', 'payment_out'].includes(t.type))
    .reduce((sum: number, t: any) => sum + Number(t.amount ?? 0), 0);
  const profit = revenue - expenses;

  const finances = [
    { label: 'Revenue', value: `${currency} ${revenue.toLocaleString()}` },
    { label: 'Expenses', value: `${currency} ${expenses.toLocaleString()}` },
    { label: 'Profit', value: `${currency} ${profit.toLocaleString()}` },
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

export const ExecutiveSuppliersPage = () => {
  const { data, isLoading } = useSuppliersQuery();

  if (isLoading) return <PageShell title='Suppliers' description='Manage supplier relationships and inventory partners.'><CardSkeleton /></PageShell>;

  const suppliers = data?.suppliers ?? data ?? [];

  return (
    <PageShell title='Suppliers' description='Manage supplier relationships and inventory partners.'>
      <div className='space-y-4'>
        {suppliers.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No suppliers yet.</p>
        ) : (
          suppliers.slice(0, 10).map((supplier: any) => (
            <div key={supplier.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
              <p className='font-semibold'>{supplier.name}</p>
              <p className='text-sm text-muted-foreground'>{supplier.phone}</p>
              <p className='text-sm text-muted-foreground'>{supplier.email}</p>
            </div>
          ))
        )}
      </div>
    </PageShell>
  );
};

export const ExecutivePromotionsPage = () => {
  const { data, isLoading } = useBranchPromotionsQuery();

  if (isLoading) return <PageShell title='Promotions' description='Create and review current marketing promotions.'><CardSkeleton /></PageShell>;

  const promotions = data?.promotions ?? data ?? [];

  return (
    <PageShell title='Promotions' description='Create and review current marketing promotions.'>
      <div className='space-y-4'>
        {promotions.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No active promotions.</p>
        ) : (
          promotions.slice(0, 10).map((promo: any) => (
            <div key={promo.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
              <div className='flex items-center justify-between'>
                <p className='font-semibold'>{promo.name ?? promo.title}</p>
                <Badge variant={promo.status === 'active' ? 'default' : 'secondary'}>{promo.status}</Badge>
              </div>
              <p className='text-sm text-muted-foreground'>{promo.description}</p>
            </div>
          ))
        )}
      </div>
    </PageShell>
  );
};

export const ExecutiveCouponsPage = () => {
  const { data, isLoading } = useBranchPromotionsQuery();

  if (isLoading) return <PageShell title='Coupons' description='Track your coupon codes and current usage status.'><CardSkeleton /></PageShell>;

  const coupons = data?.coupons ?? [];

  return (
    <PageShell title='Coupons' description='Track your coupon codes and current usage status.'>
      <div className='space-y-4'>
        {coupons.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No coupons available.</p>
        ) : (
          coupons.slice(0, 10).map((coupon: any) => (
            <div key={coupon.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
              <p className='font-semibold'>{coupon.code}</p>
              <p className='text-sm text-muted-foreground'>{coupon.discount}</p>
              <Badge variant='outline'>{coupon.status}</Badge>
            </div>
          ))
        )}
      </div>
    </PageShell>
  );
};

export const ExecutiveSettingsPage = () => (
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
