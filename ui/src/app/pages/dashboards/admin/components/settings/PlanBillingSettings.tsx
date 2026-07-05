import { useState, useEffect } from 'react';
import { useGetSubscriptionsQuery, useCreateSubscriptionMutation } from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Crown, Clock, Loader2, ArrowUpRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { Link } from 'react-router-dom';

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
  const [createSubscription, { isLoading: isCreating }] = useCreateSubscriptionMutation();

  const subscriptions = subsData?.subscriptions ?? [];
  const plans = plansData?.plans ?? [];

  const activeSubscription = subscriptions.find((s: any) => s.status === 'active');
  const activePlan = activeSubscription?.plan;
  const planPrice = Number(activePlan?.monthly_price ?? 0);
  const currency = activePlan?.currency ?? 'UGX';

  const [changePlanOpen, setChangePlanOpen] = useState(false);
  const [selectedPlanId, setSelectedPlanId] = useState('');

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
    </div>
  );
};
