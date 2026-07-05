import { useState, useEffect } from 'react';
import { useGetSubscriptionsQuery, useCreateSubscriptionMutation } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
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
import { Crown, Clock, CreditCard, Plus, Loader2, ArrowUpRight, CheckCircle, XCircle } from 'lucide-react';
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
      <Clock className='h-4 w-4 text-muted-foreground' />
      <span className={cn('font-semibold', daysLeft <= 7 ? 'text-amber-500' : 'text-foreground')}>
        {daysLeft} day{daysLeft !== 1 ? 's' : ''} remaining
      </span>
    </div>
  );
};

export const PlanBillingSettings = () => {
  const { data: subsData, isLoading: subsLoading } = useGetSubscriptionsQuery();
  const { data: plansData } = useGetPlansQuery();
  const { data: paymentsData } = useGetSubscriptionPaymentsQuery();
  const { data: paymentSettings } = useGetPaymentSettingsQuery();
  const [createSubscription, { isLoading: isCreating }] = useCreateSubscriptionMutation();
  const [createPayment, { isLoading: isPaying }] = useCreateSubscriptionPaymentMutation();

  const subscriptions = subsData?.subscriptions ?? [];
  const plans = plansData?.plans ?? [];
  const payments = paymentsData?.subscription_payments ?? [];
  const paymentMethods = paymentSettings?.methods ?? paymentSettings?.settings ?? [];

  const activeSubscription = subscriptions.find((s: any) => s.status === 'active');
  const activePlan = activeSubscription?.plan;
  const planPrice = Number(activePlan?.monthly_price ?? 0);
  const currency = activePlan?.currency ?? 'UGX';

  const [changePlanOpen, setChangePlanOpen] = useState(false);
  const [selectedPlanId, setSelectedPlanId] = useState('');

  const [payOpen, setPayOpen] = useState(false);
  const [payForm, setPayForm] = useState({
    payment_method_id: '',
    amount_paid: String(planPrice),
    transaction_id: '',
    number_paid: '',
    notes: '',
  });

  if (subsLoading) return <PageLoadingState />;

  const handleChangePlan = async () => {
    if (!selectedPlanId) {
      toast.error('Select a plan');
      return;
    }
    try {
      await createSubscription({ plan_id: Number(selectedPlanId) }).unwrap();
      toast.success('Plan changed successfully');
      setChangePlanOpen(false);
      setSelectedPlanId('');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to change plan');
    }
  };

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
      {activePlan ? (
        <Card className='border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10'>
          <CardContent className='p-6'>
            <div className='flex items-start justify-between gap-4 flex-wrap'>
              <div className='flex items-start gap-4'>
                <div className='rounded-xl bg-primary/10 p-3'>
                  <Crown className='h-6 w-6 text-primary' />
                </div>
                <div>
                  <p className='text-sm text-muted-foreground'>Current Plan</p>
                  <p className='text-xl font-bold'>{activePlan.name}</p>
                  <p className='text-sm text-muted-foreground mt-1'>
                    {currency} {planPrice.toLocaleString()}/mo
                  </p>
                </div>
              </div>
              <div className='flex items-center gap-3 flex-wrap'>
                <DayCountdown endsAt={activeSubscription?.ends_at} />
                <Badge variant='outline' className='bg-green-500/10 text-green-600 border-green-500/20'>Active</Badge>
                <Dialog open={changePlanOpen} onOpenChange={setChangePlanOpen}>
                  <DialogTrigger asChild>
                    <Button variant='outline' size='sm'>
                      <ArrowUpRight className='h-4 w-4 mr-1.5' />
                      Change Plan
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Change Plan</DialogTitle>
                      <DialogDescription>
                        Your current active subscription will be replaced.
                      </DialogDescription>
                    </DialogHeader>
                    <div className='space-y-4'>
                      <div className='space-y-2'>
                        <Label>New Plan</Label>
                        <Select value={selectedPlanId} onValueChange={setSelectedPlanId}>
                          <SelectTrigger>
                            <SelectValue placeholder='Select a plan' />
                          </SelectTrigger>
                          <SelectContent>
                            {plans.map((plan: any) => (
                              <SelectItem key={plan.id} value={String(plan.id)}>
                                {plan.name} - {plan.currency} {Number(plan.monthly_price).toLocaleString()}/mo
                                {plan.mark ? ` (${plan.mark})` : ''}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                    <DialogFooter>
                      <Button variant='outline' onClick={() => setChangePlanOpen(false)}>Cancel</Button>
                      <Button onClick={handleChangePlan} disabled={isCreating || !selectedPlanId}>
                        {isCreating && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                        Confirm Change
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </div>
            </div>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardContent className='p-6 text-center'>
            <Crown className='h-12 w-12 text-muted-foreground mx-auto mb-3' />
            <h3 className='text-lg font-semibold'>No Active Plan</h3>
            <p className='text-sm text-muted-foreground mt-1'>Choose a plan to get started with premium features.</p>
            <Button className='mt-4' asChild>
              <Link to='/pricing'>
                <ArrowUpRight className='h-4 w-4 mr-2' />
                View Plans
              </Link>
            </Button>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader className='flex flex-row items-center justify-between'>
          <CardTitle className='text-lg flex items-center gap-2'>
            <CreditCard className='h-5 w-5' />
            Payment History
          </CardTitle>
          <Dialog open={payOpen} onOpenChange={setPayOpen}>
            <DialogTrigger asChild>
              <Button size='sm'>
                <Plus className='h-4 w-4 mr-2' />
                Add Payment
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Make a Payment</DialogTitle>
                <DialogDescription>
                  {activePlan
                    ? `Pay for ${activePlan.name} (${currency} ${planPrice.toLocaleString()}/mo)`
                    : 'You need an active subscription to make a payment.'}
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
