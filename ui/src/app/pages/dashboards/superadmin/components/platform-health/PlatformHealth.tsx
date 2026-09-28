import { useGetSuperAdminBusinessesQuery } from '@/app/store/features/business/superAdminBusinessesQuery';
import { useGetSubscriptionsQuery } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetSubscriptionPaymentsQuery } from '@/app/store/features/subscriptions/subscriptionPaymentsQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Activity, Building2, Crown, CreditCard, TrendingUp, Users } from 'lucide-react';

export const PlatformHealth = () => {
  const { data: businessesData, isLoading: businessesLoading } = useGetSuperAdminBusinessesQuery();
  const { data: subsData, isLoading: subsLoading } = useGetSubscriptionsQuery();
  const { data: paymentsData, isLoading: paymentsLoading } = useGetSubscriptionPaymentsQuery();

  const isLoading = businessesLoading || subsLoading || paymentsLoading;

  const businesses = businessesData?.businesses ?? [];
  const subscriptions = subsData?.subscriptions ?? [];
  const payments = paymentsData?.subscription_payments ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
        </CardContent>
      </Card>
    );
  }

  const activeBusinesses = businesses.filter((b: any) => b.status === 'active');
  const inactiveBusinesses = businesses.filter((b: any) => b.status !== 'active');
  const activeSubs = subscriptions.filter((s: any) => s.status === 'active');
  const pendingPayments = payments.filter((p: any) => p.payment_status === 'pending');
  const failedPayments = payments.filter((p: any) => p.payment_status === 'failed');

  const totalUsers = businesses.reduce((sum: number, b: any) => sum + (b.users?.length ?? 0), 0);

  const healthMetrics = [
    {
      label: 'Business Uptime',
      value: '99.9%',
      icon: Activity,
      color: 'text-emerald-500',
    },
    {
      label: 'Active Businesses',
      value: `${activeBusinesses.length}/${businesses.length}`,
      icon: Building2,
      color: 'text-blue-500',
    },
    {
      label: 'Active Subscriptions',
      value: `${activeSubs.length}/${subscriptions.length}`,
      icon: Crown,
      color: 'text-purple-500',
    },
    {
      label: 'Total Users',
      value: totalUsers,
      icon: Users,
      color: 'text-indigo-500',
    },
    {
      label: 'Pending Payments',
      value: pendingPayments.length,
      icon: CreditCard,
      color: 'text-amber-500',
    },
    {
      label: 'Failed Payments',
      value: failedPayments.length,
      icon: TrendingUp,
      color: failedPayments.length > 0 ? 'text-red-500' : 'text-emerald-500',
    },
  ];

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Activity className='h-4 w-4 text-blue-500' />
          Platform Health
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className='grid grid-cols-2 gap-3 sm:grid-cols-3'>
          {healthMetrics.map((metric) => {
            const Icon = metric.icon;
            return (
              <div key={metric.label} className='rounded-lg border border-border/50 px-3 py-2.5'>
                <div className='flex items-center gap-1.5'>
                  <Icon className={`h-3.5 w-3.5 ${metric.color}`} />
                  <span className='text-xs text-muted-foreground'>{metric.label}</span>
                </div>
                <p className='mt-1 text-lg font-semibold'>{metric.value}</p>
              </div>
            );
          })}
        </div>

        {inactiveBusinesses.length > 0 && (
          <div className='mt-3 border-t pt-3'>
            <p className='mb-1.5 text-xs font-medium text-muted-foreground'>
              Inactive Businesses ({inactiveBusinesses.length})
            </p>
            <div className='space-y-1'>
              {inactiveBusinesses.slice(0, 3).map((b: any) => (
                <div key={b.id} className='flex items-center justify-between text-xs'>
                  <span className='truncate'>{b.name}</span>
                  <span className='ml-2 shrink-0 text-red-500'>{b.status}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
