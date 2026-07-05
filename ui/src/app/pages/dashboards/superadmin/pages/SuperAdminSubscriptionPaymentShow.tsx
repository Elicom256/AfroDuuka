import { useParams, useNavigate } from 'react-router-dom';
import { useGetSubscriptionPaymentQuery } from '@/app/store/features/subscriptions/subscriptionPaymentsQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { CreditCard, ArrowLeft, Building2, UserCheck, Clock, Hash, FileText } from 'lucide-react';

const paymentStatusColors: Record<string, string> = {
  pending: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  completed: 'bg-green-500/10 text-green-600 border-green-500/20',
  failed: 'bg-red-500/10 text-red-600 border-red-500/20',
  rejected: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const SuperAdminSubscriptionPaymentShow = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, isLoading } = useGetSubscriptionPaymentQuery(Number(id));

  if (isLoading) return <PageLoadingState />;
  if (!data?.subscription_payment) {
    return (
      <div className='text-center py-12'>
        <p className='text-muted-foreground'>Payment not found.</p>
        <Button variant='outline' className='mt-4' onClick={() => navigate('/superadmin/subscription-payments')}>Go Back</Button>
      </div>
    );
  }

  const payment = data.subscription_payment;

  return (
    <div className='space-y-6'>
      <Button variant='ghost' size='sm' onClick={() => navigate('/superadmin/subscription-payments')}>
        <ArrowLeft className='h-4 w-4 mr-2' />
        Back to Subscription Payments
      </Button>

      <div className='flex items-start justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <CreditCard className='h-8 w-8' />
            Payment Details
          </h1>
          <p className='text-muted-foreground mt-1'>View subscription payment information</p>
        </div>
        <Badge className={paymentStatusColors[payment.payment_status] ?? ''} variant='outline'>
          {payment.payment_status}
        </Badge>
      </div>

      <div className='grid gap-6 md:grid-cols-2'>
        <Card>
          <CardHeader><CardTitle>Payment Info</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center justify-between'>
              <span className='text-sm text-muted-foreground'>Amount Paid</span>
              <span className='font-semibold text-lg'>
                {payment.subscription?.plan?.currency ?? 'UGX'} {Number(payment.amount_paid).toLocaleString()}
              </span>
            </div>
            <div className='flex items-center gap-2 text-sm'>
              <Hash className='h-4 w-4 text-muted-foreground' />
              <span>Transaction: {payment.transaction_id ?? 'N/A'}</span>
            </div>
            <div className='flex items-center gap-2 text-sm'>
              <FileText className='h-4 w-4 text-muted-foreground' />
              <span>Phone: {payment.number_paid ?? 'N/A'}</span>
            </div>
            <div className='flex items-center gap-2 text-sm'>
              <Clock className='h-4 w-4 text-muted-foreground' />
              <span>Date: {payment.created_at ? new Date(payment.created_at).toLocaleString() : '-'}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Business / Plan</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center gap-2'>
              <Building2 className='h-4 w-4 text-muted-foreground' />
              <span className='font-medium'>{payment.subscription?.business?.name ?? `Business #${payment.subscription?.business_id}`}</span>
            </div>
            <div className='text-sm text-muted-foreground'>
              Plan: {payment.subscription?.plan?.name ?? 'N/A'}
            </div>
            <div className='text-sm text-muted-foreground'>
              {payment.subscription?.plan?.currency ?? 'UGX'} {Number(payment.subscription?.plan?.monthly_price ?? 0).toLocaleString()}/mo
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Payment Method</CardTitle></CardHeader>
          <CardContent>
            <p className='font-medium capitalize'>
              {payment.payment_method?.method?.replace(/_/g, ' ') ?? 'N/A'}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Verification</CardTitle></CardHeader>
          <CardContent className='space-y-3'>
            <div className='flex items-center gap-2 text-sm'>
              <UserCheck className='h-4 w-4 text-muted-foreground' />
              <span>Verified by: {payment.verified_by?.username ?? payment.verified_by ?? 'Not yet verified'}</span>
            </div>
            {payment.verified_at && (
              <div className='flex items-center gap-2 text-sm'>
                <Clock className='h-4 w-4 text-muted-foreground' />
                <span>Verified at: {new Date(payment.verified_at).toLocaleString()}</span>
              </div>
            )}
            {payment.rejection_reason && (
              <div className='text-sm'>
                <span className='text-muted-foreground'>Rejection reason:</span>
                <p className='mt-1 text-red-600'>{payment.rejection_reason}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
