import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
  CardFooter,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SectionHeader } from '@/app/pages/public/components/SectionHeader';
import { useGetPricingsQuery } from '@/app/store/features/pricing/pricingQuery';
import { Skeleton } from '@/components/ui/skeleton';
import { Check } from 'lucide-react';

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Pricing {
  id: number;
  name: string;
  slug: string;
  mark: string;
  description?: string;
  monthly_price: string;
  yearly_price: string;
  features?: string[];
  limits?: Limits;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

const getBadgeVariant = (mark: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
  if (mark.toLowerCase().includes('popular')) return 'default';
  if (mark.toLowerCase().includes('value')) return 'secondary';
  return 'outline';
};

const PricingCard = ({ pricing }: { pricing: Pricing }) => {
  const isMostPopular = pricing.mark.toLowerCase().includes('popular');

  return (
    <Card
      className={`relative flex flex-col transition-all duration-300 ${
        isMostPopular
          ? 'border-primary/50 bg-linear-to-br from-primary/5 to-primary/0 shadow-lg ring-1 ring-primary/20'
          : 'border-border/70 hover:border-border hover:shadow-md'
      }`}
    >
      {/* {isMostPopular && (
        <div className='absolute -top-3 left-1/2 -translate-x-1/2'>
          <Badge className='bg-primary text-primary-foreground shadow-md'>t</Badge>
        </div>
      )} */}

      <CardHeader className='pb-4'>
        <div className='flex items-start justify-between gap-4'>
          <div className='flex-1'>
            <CardTitle className='text-xl font-bold'>{pricing.name}</CardTitle>

            <CardDescription className='mt-1 text-sm line-clamp-2'>{pricing.description}</CardDescription>
          </div>
          {isMostPopular && (
            <Badge className='bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'>
              {pricing.mark}
            </Badge>
          )}
        </div>
      </CardHeader>

      <CardContent className='flex-1 space-y-6'>
        <div className='space-y-2'>
          <div className='flex items-baseline gap-2'>
            <p className='text-2xl sm:text-3xl font-bold tracking-tight'>
              {formatPrice(pricing.monthly_price, pricing.currency)}
            </p>
            <span className='text-sm font-medium text-muted-foreground'>/mo</span>
          </div>
          {Number(pricing.yearly_price) > 0 && (
            <p className='text-xs text-muted-foreground'>
              {formatPrice(pricing.yearly_price, pricing.currency)} billed yearly
            </p>
          )}
        </div>

        {pricing.features && pricing.features.length > 0 && (
          <ul className='space-y-3'>
            {pricing.features.map((feature: string, i: number) => (
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

const formatPrice = (price: string, currency: string) => {
  console.log(typeof price);
  if (currency === 'UGX') {
    return `UGX ${parseInt(price).toLocaleString()}`;
  }
  return `${currency} ${price.toLocaleString()}`;
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
  const { data: pricingData, isLoading: pricingLoading } = useGetPricingsQuery();
  const pricings = pricingData?.pricings ?? [];

  return (
    <section className='mt-20'>
      <SectionHeader
        badge='Pricing'
        title='Simple, transparent pricing'
        description='Choose the plan that fits your business. All plans include a 14-day free trial.'
      />
      <div className='mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4'>
        {pricingLoading
          ? [1, 2, 3, 4].map((i) => <PricingCardSkeleton key={i} />)
          : pricings.map((pricing: any) => <PricingCard key={pricing.id} pricing={pricing} />)}
      </div>
    </section>
  );
};
