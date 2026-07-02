import { useGetSubscriptionsQuery } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { CalendarDays, CreditCard, Crown, Tag } from 'lucide-react';

const statusColors: Record<string, string> = {
  active: 'bg-green-500/10 text-green-600 border-green-500/20',
  paused: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
  expired: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const AdminSubscriptionsPage = () => {
  const { data, isLoading } = useGetSubscriptionsQuery();
  const subscriptions = data?.subscriptions ?? [];

  const activeSubscription = subscriptions.find((s: any) => s.status === 'active');
  const activePlan = activeSubscription?.plan;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Crown className='h-8 w-8' />
          Subscriptions
        </h1>
        <p className='text-muted-foreground mt-1'>Manage your business subscriptions and plans</p>
      </div>

      {activePlan && (
        <Card className='border-primary/20 bg-primary/5'>
          <CardContent className='flex items-center gap-4 p-5'>
            <div className='rounded-xl bg-primary/10 p-3'>
              <Crown className='h-6 w-6 text-primary' />
            </div>
            <div className='flex-1'>
              <p className='text-sm text-muted-foreground'>Currently Active Plan</p>
              <p className='text-lg font-semibold'>{activePlan?.pricing?.name ?? 'N/A'}</p>
            </div>
            <Badge className='bg-green-500/10 text-green-600 border-green-500/20 text-xs px-3 py-1'>
              {activeSubscription.status}
            </Badge>
          </CardContent>
        </Card>
      )}

      <Card className='border-border/70'>
        <CardHeader>
          <CardTitle className='text-lg flex items-center gap-2'>
            <CreditCard className='h-5 w-5' />
            All Subscriptions
          </CardTitle>
        </CardHeader>
        <CardContent>
          {subscriptions.length === 0 ? (
            <p className='text-sm text-muted-foreground text-center py-8'>No subscriptions found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Plan</TableHead>
                  <TableHead>Pricing</TableHead>
                  <TableHead>Payment Method</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {subscriptions.map((sub: any) => (
                  <TableRow key={sub.id}>
                    <TableCell className='font-medium'>
                      <div className='flex items-center gap-2'>
                        <Tag className='h-4 w-4 text-muted-foreground' />
                        {sub.plan?.pricing?.name ?? 'N/A'}
                      </div>
                    </TableCell>
                    <TableCell>
                      {sub.plan?.pricing
                        ? `${sub.plan.pricing.currency} ${Number(sub.plan.pricing.monthly_price).toLocaleString()}/mo`
                        : 'N/A'}
                    </TableCell>
                    <TableCell>{sub.payment_method?.method?.replace('_', ' ') ?? 'N/A'}</TableCell>
                    <TableCell>
                      <Badge className={statusColors[sub.status] ?? ''} variant='outline'>
                        {sub.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className='flex items-center gap-1.5 text-sm'>
                        <CalendarDays className='h-3.5 w-3.5 text-muted-foreground' />
                        {sub.start_date ? new Date(sub.start_date).toLocaleDateString() : '-'}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className='flex items-center gap-1.5 text-sm'>
                        <CalendarDays className='h-3.5 w-3.5 text-muted-foreground' />
                        {sub.end_date ? new Date(sub.end_date).toLocaleDateString() : '-'}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
