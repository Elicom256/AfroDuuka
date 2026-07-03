import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SectionHeader } from '@/app/pages/public/components/SectionHeader';
import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
import { Skeleton } from '@/components/ui/skeleton';
import { Check, Percent } from 'lucide-react';

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Plan {
  id: number;
  name: string;
  slug: string;
  mark: string;
  description?: string;
  monthly_price: string;
  yearly_price: string;
  discount_percentage?: number;
  billing_cycle?: string;
  features?: string[];
  limits?: Limits;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

const computeDiscounted = (price: number, discountPercent: number) => {
  if (!discountPercent) return price;
  return price - (price * discountPercent) / 100;
};

const PlanCard = ({ plan }: { plan: Plan }) => {
  const isMostPopular = plan.mark.toLowerCase().includes('popular');
  const monthlyPrice = Number(plan.monthly_price);
  const yearlyPrice = Number(plan.yearly_price);
  const discountPct = plan.discount_percentage ?? 0;
  const hasDiscount = discountPct > 0;

  const discountedMonthly = computeDiscounted(monthlyPrice, discountPct);
  const discountedYearly = computeDiscounted(yearlyPrice, discountPct);

  return (
    <Card
      className={`relative flex flex-col transition-all duration-300 ${
        isMostPopular
          ? 'border-primary/50 bg-linear-to-br from-primary/5 to-primary/0 shadow-lg ring-1 ring-primary/20'
          : 'border-border/70 hover:border-border hover:shadow-md'
      }`}
    >
      {hasDiscount && (
        <div className='absolute -top-3 right-4 z-10'>
          <Badge className='flex items-center gap-1 bg-amber-500 text-white shadow-md hover:bg-amber-500'>
            <Percent className='h-3 w-3' />
            {discountPct}% OFF
          </Badge>
        </div>
      )}

      <CardHeader className='pb-4'>
        <div className='flex items-start justify-between gap-4'>
          <div className='flex-1'>
            <CardTitle className='text-xl font-bold'>{plan.name}</CardTitle>

            <CardDescription className='mt-1 text-sm line-clamp-2'>{plan.description}</CardDescription>
          </div>
          {isMostPopular && (
            <Badge className='bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'>{plan.mark}</Badge>
          )}
        </div>
      </CardHeader>

      <CardContent className='flex-1 space-y-6'>
        <div className='space-y-2'>
          {hasDiscount ? (
            <div className='space-y-1'>
              <div className='flex items-baseline gap-2'>
                <p className='text-2xl sm:text-3xl font-bold tracking-tight text-primary'>
                  {formatPrice(discountedMonthly, plan.currency)}
                </p>
                <span className='text-sm font-medium text-muted-foreground'>/mo</span>
              </div>
              <div className='flex items-center gap-2'>
                <span className='text-sm text-muted-foreground line-through'>
                  {formatPrice(monthlyPrice, plan.currency)}
                </span>
                <span className='text-xs font-medium text-green-600 dark:text-green-400'>
                  Save {formatPrice(monthlyPrice - discountedMonthly, plan.currency)}/mo
                </span>
              </div>
            </div>
          ) : (
            <div className='flex items-baseline gap-2'>
              <p className='text-2xl sm:text-3xl font-bold tracking-tight'>
                {formatPrice(monthlyPrice, plan.currency)}
              </p>
              <span className='text-sm font-medium text-muted-foreground'>/mo</span>
            </div>
          )}

          {yearlyPrice > 0 && (
            <div>
              {hasDiscount ? (
                <div className='flex items-center gap-2'>
                  <span className='text-xs font-medium text-green-600 dark:text-green-400'>
                    {formatPrice(discountedYearly, plan.currency)}
                  </span>
                  <span className='text-xs text-muted-foreground line-through'>
                    {formatPrice(yearlyPrice, plan.currency)}
                  </span>
                  <span className='text-xs text-muted-foreground'>billed yearly</span>
                </div>
              ) : (
                <p className='text-xs text-muted-foreground'>
                  {formatPrice(yearlyPrice, plan.currency)} billed yearly
                </p>
              )}
            </div>
          )}
        </div>

        {plan.features && plan.features.length > 0 && (
          <ul className='space-y-3'>
            {plan.features.map((feature: string, i: number) => (
              <li key={i} className='flex items-start gap-3 text-sm'>
                <Check className='h-4 w-4 mt-0.5 shrink-0 text-primary' />
                <span className='text-foreground/80'>{feature}</span>
              </li>
            ))}
          </ul>
        )}
      </CardContent>

      <CardFooter>
        <Button className='w-full' variant={isMostPopular ? 'default' : 'outline'} asChild>
          <Link to='/signup'>Get Started</Link>
        </Button>
      </CardFooter>
    </Card>
  );
};

const formatPrice = (price: number, currency: string) => {
  const rounded = Math.round(price);
  if (currency === 'UGX') {
    return `UGX ${rounded.toLocaleString()}`;
  }
  return `${currency} ${rounded.toLocaleString()}`;
};

const PricingCardSkeleton = () => (
  <Card className='border-border/70'>
    <CardHeader className='pb-4'>
      <div className='space-y-3'>
        <Skeleton className='h-6 w-24' />
        <Skeleton className='h-4 w-full max-w-xs' />
      </div>
    </CardHeader>
    <CardContent className='space-y-6'>
      <Skeleton className='h-10 w-32' />
      <div className='space-y-3'>
        {[...Array(5)].map((_, i) => (
          <Skeleton key={i} className='h-4 w-full' />
        ))}
      </div>
    </CardContent>
    <CardFooter>
      <Skeleton className='h-10 w-full' />
    </CardFooter>
  </Card>
);

export const PricingSection = () => {
  const { data: planData, isLoading: planLoading } = useGetPlansQuery();
  const plans = planData?.plans ?? [];

  return (
    <section className='mt-20'>
      <SectionHeader
        badge='Pricing'
        title='Simple, transparent pricing'
        description='Choose the plan that fits your business. All plans include a 14-day free trial.'
      />
      <div className='mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4'>
        {planLoading
          ? [1, 2, 3, 4].map((i) => <PricingCardSkeleton key={i} />)
          : plans.map((plan: any) => <PlanCard key={plan.id} plan={plan} />)}
      </div>
    </section>
  );
};
