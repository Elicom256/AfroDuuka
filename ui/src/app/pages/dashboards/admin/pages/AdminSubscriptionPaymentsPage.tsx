import { useState, useEffect } from 'react';
import { useGetSubscriptionsQuery } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetSubscriptionPaymentsQuery, useCreateSubscriptionPaymentMutation } from '@/app/store/features/subscriptions/subscriptionPaymentsQuery';
import { useGetPaymentSettingsQuery } from '@/app/store/features/business/settings/payment';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { CreditCard, Plus, Loader2, Crown, Clock, CheckCircle, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { Link } from 'react-router-dom';

const paymentStatusColors: Record<string, string> = {
  pending: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  completed: 'bg-green-500/10 text-green-600 border-green-500/20',
  failed: 'bg-red-500/10 text-red-600 border-red-500/20',
  rejected: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

const DayCountdown = ({ endsAt }: { endsAt: string | null }) => {
  const [daysLeft, setDaysLeft] = useState<number | null>(null);

  useEffect(() => {
    if (!endsAt) {
      setDaysLeft(null);
      return;
    }
    const calc = () => {
      const now = new Date();
      const end = new Date(endsAt);
      const diff = end.getTime() - now.getTime();
      setDaysLeft(diff > 0 ? Math.ceil(diff / (1000 * 60 * 60 * 24)) : 0);
    };
    calc();
    const interval = setInterval(calc, 1000 * 60 * 60);
    return () => clearInterval(interval);
  }, [endsAt]);

  if (daysLeft === null) return null;
  if (daysLeft <= 0) return <Badge variant='outline' className='text-red-500 border-red-500/30'>Expired</Badge>;

  return (
    <div className='flex items-center gap-1.5 text-sm'>
      <Clock className='h-3.5 w-3.5 text-muted-foreground' />
      <span className={cn('font-medium', daysLeft <= 7 ? 'text-amber-500' : 'text-foreground')}>
        {daysLeft} day{daysLeft !== 1 ? 's' : ''}
      </span>
    </div>
  );
};

export const AdminSubscriptionPaymentsPage = () => {
  const { data: subsData, isLoading: subsLoading } = useGetSubscriptionsQuery();
  const { data: paymentsData, isLoading: paymentsLoading } = useGetSubscriptionPaymentsQuery();
  const { data: paymentSettings } = useGetPaymentSettingsQuery();
  const [createPayment, { isLoading: isPaying }] = useCreateSubscriptionPaymentMutation();

  const subscriptions = subsData?.subscriptions ?? [];
  const payments = paymentsData?.subscription_payments ?? [];
  const paymentMethods = paymentSettings?.methods ?? paymentSettings?.settings ?? [];

  const activeSubscription = subscriptions.find((s: any) => s.status === 'active');
  const activePlan = activeSubscription?.plan;
  const planPrice = Number(activePlan?.monthly_price ?? 0);
  const currency = activePlan?.currency ?? 'UGX';

  const [payOpen, setPayOpen] = useState(false);
  const [payForm, setPayForm] = useState({
    payment_method_id: '',
    amount_paid: String(planPrice),
    transaction_id: '',
    number_paid: '',
    notes: '',
  });

  if (subsLoading || paymentsLoading) return <PageLoadingState />;

  const handlePay = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeSubscription) {
      toast.error('No active subscription to pay for');
      return;
    }
    if (!payForm.payment_method_id) {
      toast.error('Select a payment method');
      return;
    }
    if (!payForm.number_paid) {
      toast.error('Enter the phone number used for payment');
      return;
    }
    try {
      await createPayment({
        subscription_id: activeSubscription.id,
        payment_method_id: Number(payForm.payment_method_id),
        amount_paid: Number(payForm.amount_paid),
        transaction_id: payForm.transaction_id || undefined,
        number_paid: payForm.number_paid,
        payment_status: 'pending',
        notes: payForm.notes || undefined,
      }).unwrap();
      toast.success('Payment submitted. Awaiting verification.');
      setPayOpen(false);
      setPayForm({
        payment_method_id: '',
        amount_paid: String(planPrice),
        transaction_id: '',
        number_paid: '',
        notes: '',
      });
    } catch (err: any) {
      toast.error(err?.data?.message || 'Payment failed');
    }
  };

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <CreditCard className='h-8 w-8' />
            Payments
          </h1>
          <p className='text-muted-foreground mt-1'>View payment history and make subscription payments</p>
        </div>
      </div>

      {activePlan && (
        <Card className='border-primary/20 bg-primary/5'>
          <CardContent className='flex items-center gap-4 p-5'>
            <div className='rounded-xl bg-primary/10 p-3'>
              <Crown className='h-6 w-6 text-primary' />
            </div>
            <div className='flex-1'>
              <p className='text-sm text-muted-foreground'>Active Plan</p>
              <p className='text-lg font-semibold'>{activePlan.name}</p>
            </div>
            <div className='flex items-center gap-3'>
              <DayCountdown endsAt={activeSubscription?.ends_at} />
              <Badge variant='outline' className='bg-green-500/10 text-green-600 border-green-500/20'>Active</Badge>
            </div>
          </CardContent>
        </Card>
      )}

      <Card className='border-border/70'>
        <CardHeader className='flex flex-row items-center justify-between'>
          <CardTitle className='text-lg flex items-center gap-2'>
            <CreditCard className='h-5 w-5' />
            Payment History
          </CardTitle>
          {activePlan && (
            <Dialog open={payOpen} onOpenChange={setPayOpen}>
              <DialogTrigger asChild>
                <Button size='sm'>
                  <Plus className='h-4 w-4 mr-2' />
                  Make Payment
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Make a Payment</DialogTitle>
                  <DialogDescription>
                    Pay for <strong>{activePlan.name}</strong> ({currency} {planPrice.toLocaleString()}/mo)
                  </DialogDescription>
                </DialogHeader>
                <form onSubmit={handlePay} className='space-y-4'>
                  <div className='space-y-2'>
                    <Label>Amount ({currency})</Label>
                    <Input type='number' value={payForm.amount_paid} onChange={(e) => setPayForm({ ...payForm, amount_paid: e.target.value })} />
                  </div>
                  <div className='space-y-2'>
                    <Label>Payment Method</Label>
                    <Select value={payForm.payment_method_id} onValueChange={(v) => setPayForm({ ...payForm, payment_method_id: v })}>
                      <SelectTrigger><SelectValue placeholder='Select method' /></SelectTrigger>
                      <SelectContent>
                        {paymentMethods.map((pm: any) => (
                          <SelectItem key={pm.id} value={String(pm.id)}>
                            {pm.method?.replace(/_/g, ' ')}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {paymentMethods.length === 0 && (
                      <p className='text-xs text-muted-foreground mt-1'>
                        No payment methods enabled. Configure them in{' '}
                        <Link to='/admin/settings/payment-settings' className='underline'>Payment Settings</Link>.
                      </p>
                    )}
                  </div>
                  <div className='space-y-2'>
                    <Label>Phone Number</Label>
                    <Input value={payForm.number_paid} onChange={(e) => setPayForm({ ...payForm, number_paid: e.target.value })} placeholder='+256 XXX XXX XXX' />
                  </div>
                  <div className='space-y-2'>
                    <Label>Transaction ID (optional)</Label>
                    <Input value={payForm.transaction_id} onChange={(e) => setPayForm({ ...payForm, transaction_id: e.target.value })} placeholder='Transaction reference' />
                  </div>
                  <div className='space-y-2'>
                    <Label>Notes (optional)</Label>
                    <Input value={payForm.notes} onChange={(e) => setPayForm({ ...payForm, notes: e.target.value })} placeholder='Additional info' />
                  </div>
                  <DialogFooter>
                    <Button type='button' variant='outline' onClick={() => setPayOpen(false)}>Cancel</Button>
                    <Button type='submit' disabled={isPaying}>
                      {isPaying && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                      Submit Payment
                    </Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          )}
        </CardHeader>
        <CardContent>
          {payments.length === 0 ? (
            <p className='text-sm text-muted-foreground text-center py-8'>No payments yet.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Plan</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {payments.map((payment: any) => (
                  <TableRow key={payment.id}>
                    <TableCell className='text-sm'>
                      {payment.created_at ? new Date(payment.created_at).toLocaleDateString() : '-'}
                    </TableCell>
                    <TableCell>
                      <span className='font-medium'>{payment.subscription?.plan?.name ?? 'N/A'}</span>
                    </TableCell>
                    <TableCell className='font-medium'>
                      {payment.subscription?.plan?.currency ?? 'UGX'} {Number(payment.amount_paid).toLocaleString()}
                    </TableCell>
                    <TableCell className='capitalize text-sm'>
                      {payment.payment_method?.method?.replace(/_/g, ' ') ?? 'N/A'}
                    </TableCell>
                    <TableCell>
                      <Badge className={paymentStatusColors[payment.payment_status] ?? ''} variant='outline'>
                        {payment.payment_status === 'completed' && <CheckCircle className='h-3 w-3 mr-1' />}
                        {payment.payment_status === 'rejected' && <XCircle className='h-3 w-3 mr-1' />}
                        {payment.payment_status}
                      </Badge>
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
