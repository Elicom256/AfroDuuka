import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
import {
  useCreateSubscriptionMutation,
  useGetSubscriptionsQuery,
} from '@/app/store/features/subscriptions/subscriptionsQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

export const PlanBillingSettings = () => {
  const { data: plansData, isLoading } = useGetPlansQuery();
  const { data: subscriptions } = useGetSubscriptionsQuery();
  const [createSubscription, { isLoading: isSubscribing }] = useCreateSubscriptionMutation();

  const plans = plansData?.plans ?? [];
  const mysubscriptions = subscriptions?.subscriptions;
  const current_subscribed_plan = mysubscriptions?.find((sub: any) => sub.status === 'active');
  console.log('current_subscribed_plan here==>', current_subscribed_plan);

  const handleChoosePlan = async (planId: number, planName: string) => {
    try {
      const res = await createSubscription({ plan_id: planId }).unwrap();
      console.log('res==>', res);
      toast.success(`Subscribed to ${planName}`);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to subscribe');
      console.log('error==>', err);
    }
  };

  if (isLoading) return <div className='text-center py-12'>Loading plans...</div>;

  return (
    <div className='max-w-5xl mx-auto px-4 py-12'>
      <div className='text-center mb-10'>
        <h1 className='text-4xl font-bold'>Choose Your Plan</h1>
        <p className='text-muted-foreground mt-2'>Simple, transparent pricing</p>
      </div>

      <div className='grid md:grid-cols-2  gap-6'>
        {plans.map((plan: any) => {
          const price = Number(plan.monthly_price || 0);
          const currency = plan.currency || 'UGX';

          return (
            <Card key={plan.id} className={current_subscribed_plan?.plan_id === plan.id ? "bg-green-600": 'border-2 hover:border-primary transition-all'}>
              <CardHeader className='text-center'>
                <CardTitle className='text-2xl flex items-center justify-center gap-2'>
                  <span>{plan.name}</span>

                  {mysubscriptions?.find((sub: any) => sub.status === 'active' && sub.plan_id === plan.id) && (
                    <Badge className='bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'>
                      active
                    </Badge>
                  )}
                </CardTitle>
                <div className='mt-4'>
                  <span className='text-lg font-semibold'>{currency}</span>
                  <span className='text-2xl font-semibold ml-1'>{price.toLocaleString()}</span>
                  <span className='text-muted-foreground'>/mo</span>
                </div>
              </CardHeader>

              <CardContent className='pt-2'>
                <Button
                  className='w-full text-lg py-6'
                  onClick={() => handleChoosePlan(plan.id, plan.name)}
                  disabled={isSubscribing}
                >
                  {isSubscribing ? (
                    <>
                      <Loader2 className='mr-2 h-5 w-5 animate-spin' />
                      Subscribing...
                    </>
                  ) : (
                    'Choose Plan'
                  )}
                </Button>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
};
