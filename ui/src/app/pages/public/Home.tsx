import { Link } from 'react-router-dom';
import { ArrowRight, BarChart3, PackageSearch, ShieldCheck, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { FeatureCard } from './components/FeatureCard';
import { SectionHeader } from './components/SectionHeader';
import { useGetPricingsQuery } from '@/app/store/features/pricing/pricingQuery';
import { Skeleton } from '@/components/ui/skeleton';

const features = [
  {
    icon: <PackageSearch className='h-5 w-5' />,
    title: 'Smart stock control',
    description: 'Keep item levels, reorder points and supplier details in a single, searchable dashboard.',
  },
  {
    icon: <BarChart3 className='h-5 w-5' />,
    title: 'Insightful reporting',
    description: 'Use fast analytics and trend summaries to make decisions with confidence.',
  },
  {
    icon: <ShieldCheck className='h-5 w-5' />,
    title: 'Secure local-first design',
    description: 'Built to be modern, lightweight, and ready for mobile store workflows.',
  },
];

const formatPrice = (price: number, currency: string) => {
  if (currency === 'UGX') {
    return `UGX ${price.toLocaleString()}`;
  }
  return `${currency} ${price.toLocaleString()}`;
};

const PricingCard = ({ pricing }: { pricing: any }) => {
  const isPopular = pricing.slug === 'growth';
  return (
    <Card className={`relative flex flex-col border-border/70 transition-all hover:shadow-md ${isPopular ? 'ring-2 ring-primary shadow-lg' : ''}`}>
      {isPopular && (
        <span className='absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-1 text-xs font-semibold text-primary-foreground'>
          Most Popular
        </span>
      )}
      <CardHeader>
        <CardTitle className='text-xl'>{pricing.name}</CardTitle>
        <CardDescription className='text-sm'>{pricing.description}</CardDescription>
      </CardHeader>
      <CardContent className='flex-1 space-y-6'>
        <div>
          <p className='text-3xl font-bold tracking-tight'>{formatPrice(pricing.monthly_price, pricing.currency)}</p>
          <p className='text-xs text-muted-foreground'>per month</p>
          {pricing.yearly_price > 0 && (
            <p className='text-xs text-muted-foreground mt-1'>
              {formatPrice(pricing.yearly_price, pricing.currency)} / year
            </p>
          )}
        </div>
        {pricing.features && pricing.features.length > 0 && (
          <ul className='space-y-2.5'>
            {pricing.features.map((feature: string, i: number) => (
              <li key={i} className='flex items-start gap-2 text-sm'>
                <Check className='h-4 w-4 mt-0.5 shrink-0 text-primary' />
                <span className='text-muted-foreground'>{feature}</span>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
      <CardFooter>
        <Button className='w-full' variant={isPopular ? 'default' : 'outline'} asChild>
          <Link to='/signup'>Get Started</Link>
        </Button>
      </CardFooter>
    </Card>
  );
};

export const Home: React.FC = () => {
  const { data: pricingData, isLoading: pricingLoading } = useGetPricingsQuery();
  const pricings = pricingData?.pricings ?? [];

  return (
    <div className='container mx-auto px-4 py-10 sm:py-14'>
      <section className='grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center'>
        <div className='space-y-6'>
          <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold uppercase tracking-[0.3em] text-primary'>
            Modern inventory
          </span>
          <h1 className='max-w-3xl text-4xl font-semibold tracking-tight text-foreground sm:text-5xl'>
            DuukaFlow makes inventory management easy for every shop.
          </h1>
          <p className='max-w-2xl text-muted-foreground sm:text-lg'>
            A Ugandan-made retail and stock tracking experience with clean workflows, responsive pages, and fast
            onboarding for every store owner.
          </p>

          <div className='flex flex-col gap-3 sm:flex-row'>
            <Button asChild>
              <Link to='/documentation'>Explore docs</Link>
            </Button>
            <Button asChild variant='outline'>
              <Link to='/about'>Why DuukaFlow</Link>
            </Button>
          </div>

          <div className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-3xl border border-border/70 bg-card/80 p-5 shadow-sm'>
              <p className='text-sm uppercase tracking-[0.24em] text-muted-foreground'>Trusted by stores</p>
              <p className='mt-3 text-3xl font-semibold text-foreground'>Designed for fast selling</p>
              <p className='mt-2 text-sm text-muted-foreground'>
                Build workflows that keep items moving, customers happy, and reorders on time.
              </p>
            </div>
            <div className='rounded-3xl border border-border/70 bg-card/80 p-5 shadow-sm'>
              <p className='text-sm uppercase tracking-[0.24em] text-muted-foreground'>Ready for mobile</p>
              <p className='mt-3 text-3xl font-semibold text-foreground'>Responsive from phone to desktop</p>
              <p className='mt-2 text-sm text-muted-foreground'>
                A clean UI built with Tailwind, shadcn patterns, and a focus on strong mobile UX.
              </p>
            </div>
          </div>
        </div>

        <div className='grid gap-4'>
          <div className='rounded-[2rem] bg-primary/10 p-8 shadow-sm ring-1 ring-primary/10'>
            <p className='text-sm uppercase tracking-[0.3em] text-primary'>Focus areas</p>
            <div className='mt-6 space-y-4'>
              {features.map((feature) => (
                <div key={feature.title} className='rounded-3xl border border-border/70 bg-background/90 p-5'>
                  <div className='flex items-center gap-3'>
                    <div className='inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary'>
                      {feature.icon}
                    </div>
                    <div>
                      <h3 className='text-base font-semibold text-foreground'>{feature.title}</h3>
                      <p className='text-sm text-muted-foreground'>{feature.description}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
          <div className='rounded-[2rem] border border-border/70 bg-card/80 p-8 shadow-sm'>
            <p className='text-sm uppercase tracking-[0.3em] text-muted-foreground'>Features</p>
            <div className='mt-6 space-y-4'>
              <FeatureCard
                title='Fast setup'
                description='Create your shop, add categories, and start tracking inventory within minutes.'
                icon={<ArrowRight className='h-5 w-5' />}
              />
              <FeatureCard
                title='Clear dashboards'
                description='See stock levels, sales activity, and item performance in one place.'
                icon={<BarChart3 className='h-5 w-5' />}
              />
            </div>
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className='mt-20'>
        <SectionHeader
          badge='Pricing'
          title='Simple, transparent pricing'
          description='Choose the plan that fits your business. All plans include a 14-day free trial.'
        />
        <div className='mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
          {pricingLoading
            ? Array.from({ length: 3 }).map((_, i) => (
                <Card key={i} className='border-border/70'>
                  <CardHeader>
                    <Skeleton className='h-6 w-24' />
                    <Skeleton className='h-4 w-48 mt-2' />
                  </CardHeader>
                  <CardContent className='space-y-4'>
                    <Skeleton className='h-10 w-32' />
                    <div className='space-y-2'>
                      {Array.from({ length: 5 }).map((_, j) => (
                        <Skeleton key={j} className='h-4 w-full' />
                      ))}
                    </div>
                  </CardContent>
                  <CardFooter>
                    <Skeleton className='h-10 w-full' />
                  </CardFooter>
                </Card>
              ))
            : pricings.map((pricing: any) => <PricingCard key={pricing.id} pricing={pricing} />)}
        </div>
      </section>

      <section className='mt-12 grid gap-8 rounded-[2rem] border border-border/70 bg-card/80 p-8 shadow-sm md:grid-cols-2'>
        <div>
          <SectionHeader
            badge='Built for Uganda'
            title='Inventory for shops, hawkers, and local retailers'
            description='DuukaFlow gives your team inventory visibility, order tracking, and simple stock workflows without extra complexity.'
          />
        </div>
        <div className='space-y-4 text-sm leading-7 text-muted-foreground'>
          <p>
            From market stalls to established retailers, this system is designed to keep your stock organized, easy to
            update, and ready for customer demand.
          </p>
          <p>
            Use a modern interface with fast navigation, responsive components, and a clean layout that works on phones,
            tablets, and desktop screens.
          </p>
          <p>
            Keep your store flowing with clear product summaries, reorder alerts, and a modern experience built on
            Tailwind and shadcn styling.
          </p>
        </div>
      </section>
    </div>
  );
};
