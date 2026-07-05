import { useGetSubscriptionsQuery } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetSubscriptionPaymentsQuery } from '@/app/store/features/subscriptions/subscriptionPaymentsQuery';
import { useGetAdminPlansQuery } from '@/app/store/features/plans/adminPlansQuery';
import { useGetSuperAdminBusinessesQuery } from '@/app/store/features/business/superAdminBusinessesQuery';
import { StatsCard } from '@/app/pages/dashboards/admin/components/overview/StatsCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { LayoutDashboard, Building2, Crown, CreditCard, Wallet, CalendarDays, TrendingUp } from 'lucide-react';

const statusColors: Record<string, string> = {
  active: 'bg-green-500/10 text-green-600 border-green-500/20',
  deactivated: 'bg-red-500/10 text-red-600 border-red-500/20',
  banned: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const SuperAdminDashboardPage = () => {
  const { data: subsData, isLoading: subsLoading } = useGetSubscriptionsQuery();
  const { data: paymentsData, isLoading: paymentsLoading } = useGetSubscriptionPaymentsQuery();
  const { data: plansData, isLoading: plansLoading } = useGetAdminPlansQuery();
  const { data: businessesData, isLoading: businessesLoading } = useGetSuperAdminBusinessesQuery();

  const subscriptions = subsData?.subscriptions ?? [];
  const payments = paymentsData?.subscription_payments ?? [];
  const plans = plansData?.plans ?? [];
  const businesses = businessesData?.businesses ?? [];

  const activeBusinesses = businesses.filter((b: any) => b.status === 'active');
  const activeSubscriptions = subscriptions.filter((s: any) => s.status === 'active');
  const pendingPayments = payments.filter((p: any) => p.payment_status === 'pending');
  const totalRevenue = payments
    .filter((p: any) => p.payment_status === 'completed')
    .reduce((sum: number, p: any) => sum + Number(p.amount_paid), 0);

  const recentPayments = [...payments].sort(
    (a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  ).slice(0, 5);

  if (subsLoading || paymentsLoading || plansLoading || businessesLoading) {
    return <PageLoadingState />;
  }

  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <div className='space-y-6'>
      <div className='relative overflow-hidden rounded-2xl bg-linear-to-br from-primary/5 via-primary/10 to-primary/5 p-6 border border-primary/10'>
        <div className='absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl' />
        <div className='relative flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between'>
          <div>
            <div className='flex items-center gap-2 text-sm font-medium text-muted-foreground'>
              <LayoutDashboard className='h-4 w-4' />
              Dashboard
            </div>
            <h1 className='text-2xl font-bold tracking-tight mt-1'>
              System Overview
            </h1>
            <p className='text-sm text-muted-foreground mt-0.5'>
              Manage all businesses, subscriptions, and plans.
            </p>
          </div>
          <div className='flex items-center gap-2 text-sm text-muted-foreground mt-3 sm:mt-0'>
            <CalendarDays className='h-4 w-4' />
            {dateStr}
          </div>
        </div>
      </div>

      <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
        <StatsCard
          title='Total Businesses'
          value={businesses.length}
          icon={Building2}
          description={`${activeBusinesses.length} active`}
        />
        <StatsCard
          title='Active Subscriptions'
          value={activeSubscriptions.length}
          icon={Crown}
          description={`${subscriptions.length} total`}
          iconClassName='bg-purple-500/10 text-purple-600'
        />
        <StatsCard
          title='Pending Payments'
          value={pendingPayments.length}
          icon={Wallet}
          description='Awaiting verification'
          iconClassName='bg-amber-500/10 text-amber-600'
        />
        <StatsCard
          title='Total Plans'
          value={plans.length}
          icon={CreditCard}
          description='Available subscription plans'
          iconClassName='bg-blue-500/10 text-blue-600'
        />
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2 text-lg'>
              <TrendingUp className='h-5 w-5' />
              Recent Payments
            </CardTitle>
          </CardHeader>
          <CardContent>
            {recentPayments.length === 0 ? (
              <p className='text-sm text-muted-foreground text-center py-8'>No payments yet.</p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Business</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recentPayments.map((payment: any) => (
                    <TableRow key={payment.id}>
                      <TableCell className='font-medium'>
                        {payment.subscription?.business?.name ?? `#${payment.subscription?.business_id}`}
                      </TableCell>
                      <TableCell>
                        {payment.subscription?.plan?.currency ?? 'UGX'} {Number(payment.amount_paid).toLocaleString()}
                      </TableCell>
                      <TableCell>
                        <Badge variant='outline' className={statusColors[payment.payment_status] ?? ''}>
                          {payment.payment_status}
                        </Badge>
                      </TableCell>
                      <TableCell className='text-sm text-muted-foreground'>
                        {payment.created_at ? new Date(payment.created_at).toLocaleDateString() : '-'}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2 text-lg'>
              <Building2 className='h-5 w-5' />
              Businesses Overview
            </CardTitle>
          </CardHeader>
          <CardContent>
            {businesses.length === 0 ? (
              <p className='text-sm text-muted-foreground text-center py-8'>No businesses registered.</p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Users</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Balance</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {businesses.slice(0, 5).map((business: any) => (
                    <TableRow key={business.id}>
                      <TableCell className='font-medium'>{business.name}</TableCell>
                      <TableCell className='text-sm text-muted-foreground'>
                        {business.users?.length ?? 0}
                      </TableCell>
                      <TableCell>
                        <Badge variant='outline' className={statusColors[business.status] ?? ''}>
                          {business.status}
                        </Badge>
                      </TableCell>
                      <TableCell className='text-sm'>
                        {Number(business.subscription_balance).toLocaleString()}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
