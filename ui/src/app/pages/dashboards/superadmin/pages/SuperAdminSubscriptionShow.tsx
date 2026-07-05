import { useParams, useNavigate } from 'react-router-dom';
import { useGetSubscriptionQuery } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Crown, ArrowLeft, Building2, CalendarDays, CreditCard, Wallet, Clock } from 'lucide-react';

const statusColors: Record<string, string> = {
  active: 'bg-green-500/10 text-green-600 border-green-500/20',
  paused: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
  expired: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const SuperAdminSubscriptionShow = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, isLoading } = useGetSubscriptionQuery(Number(id));

  if (isLoading) return <PageLoadingState />;
  if (!data?.subscription) {
    return (
      <div className='text-center py-12'>
        <p className='text-muted-foreground'>Subscription not found.</p>
        <Button variant='outline' className='mt-4' onClick={() => navigate('/superadmin/subscriptions')}>Go Back</Button>
      </div>
    );
  }

  const sub = data.subscription;
  const payments = sub.payments ?? [];

  return (
    <div className='space-y-6'>
      <Button variant='ghost' size='sm' onClick={() => navigate('/superadmin/subscriptions')}>
        <ArrowLeft className='h-4 w-4 mr-2' />
        Back to Subscriptions
      </Button>

      <div className='flex items-start justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <Crown className='h-8 w-8' />
            Subscription Details
          </h1>
          <p className='text-muted-foreground mt-1'>View subscription information and payment history</p>
        </div>
        <Badge className={statusColors[sub.status] ?? ''} variant='outline'>{sub.status}</Badge>
      </div>

      <div className='grid gap-6 md:grid-cols-2'>
        <Card>
          <CardHeader><CardTitle>Business Information</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center gap-2'>
              <Building2 className='h-4 w-4 text-muted-foreground' />
              <span className='font-medium'>{sub.business?.name ?? `Business #${sub.business_id}`}</span>
            </div>
            <div className='text-sm text-muted-foreground'>
              {sub.business?.email && <p>Email: {sub.business.email}</p>}
              {sub.business?.phone && <p>Phone: {sub.business.phone}</p>}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Plan Details</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center gap-2'>
              <CreditCard className='h-4 w-4 text-muted-foreground' />
              <span className='font-medium'>{sub.plan?.name ?? 'N/A'}</span>
            </div>
            {sub.plan && (
              <div className='text-sm text-muted-foreground space-y-1'>
                <p>Monthly: {sub.plan.currency ?? 'UGX'} {Number(sub.plan.monthly_price).toLocaleString()}</p>
                <p>Yearly: {sub.plan.currency ?? 'UGX'} {Number(sub.plan.yearly_price).toLocaleString()}</p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Duration</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center gap-2 text-sm'>
              <CalendarDays className='h-4 w-4 text-muted-foreground' />
              <span>Starts: {sub.starts_at ? new Date(sub.starts_at).toLocaleDateString() : '-'}</span>
            </div>
            <div className='flex items-center gap-2 text-sm'>
              <CalendarDays className='h-4 w-4 text-muted-foreground' />
              <span>Ends: {sub.ends_at ? new Date(sub.ends_at).toLocaleDateString() : '-'}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Subscription Balance</CardTitle></CardHeader>
          <CardContent>
            <p className='text-2xl font-bold'>{Number(sub.business?.subscription_balance ?? 0).toLocaleString()}</p>
            <p className='text-sm text-muted-foreground'>Current subscription balance</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Wallet className='h-5 w-5' />
            Payment History ({payments.length})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {payments.length === 0 ? (
            <p className='text-sm text-muted-foreground text-center py-8'>No payments for this subscription.</p>
          ) : (
            <div className='space-y-3'>
              {payments.map((payment: any) => (
                <div key={payment.id} className='flex items-center justify-between rounded-lg border p-4'>
                  <div className='space-y-1'>
                    <p className='font-medium'>{Number(payment.amount_paid).toLocaleString()}</p>
                    <div className='flex items-center gap-3 text-xs text-muted-foreground'>
                      <span className='flex items-center gap-1'><Clock className='h-3 w-3' />{payment.created_at ? new Date(payment.created_at).toLocaleDateString() : '-'}</span>
                      {payment.payment_method && <span>{payment.payment_method.method?.replace(/_/g, ' ')}</span>}
                    </div>
                  </div>
                  <Badge variant='outline' className={
                    payment.payment_status === 'completed' ? 'bg-green-500/10 text-green-600' :
                    payment.payment_status === 'pending' ? 'bg-amber-500/10 text-amber-600' :
                    'bg-red-500/10 text-red-600'
                  }>{payment.payment_status}</Badge>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
