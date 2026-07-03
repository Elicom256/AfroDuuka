import { Link } from 'react-router-dom';
import { ArrowRight, BarChart3, PackageSearch, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

const metrics = [
  { value: '10,000+', label: 'Items tracked daily' },
  { value: '500+', label: 'Businesses onboarded' },
  { value: '99.9%', label: 'Uptime guaranteed' },
  { value: '4.8/5', label: 'Customer rating' },
];

export const HeroSection = () => {
  return (
    <section className='relative overflow-hidden py-16 sm:py-24'>
      <div className='absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,hsl(var(--primary)/0.08),transparent_50%)]' />
      <div className='grid gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:items-center'>
        <div className='space-y-8'>
          <span className='inline-flex rounded-full bg-primary/10 px-4 py-1.5 text-sm font-semibold uppercase tracking-[0.3em] text-primary'>
            Modern inventory management
          </span>
          <h1 className='max-w-3xl text-4xl font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl'>
            Stop losing stock.{' '}
            <span className='text-primary'>Start growing your business.</span>
          </h1>
          <p className='max-w-2xl text-lg text-muted-foreground sm:text-xl'>
            DuukaFlow helps Ugandan small and medium businesses track inventory in real time, automate WhatsApp
            notifications, and make data-driven decisions — all from your phone.
          </p>

          <div className='flex flex-col gap-4 sm:flex-row'>
            <Button size='lg' asChild>
              <Link to='/signup'>
                Start free trial
                <ArrowRight className='ml-2 h-4 w-4' />
              </Link>
            </Button>
            <Button size='lg' variant='outline' asChild>
              <Link to='/documentation'>See how it works</Link>
            </Button>
          </div>

          <div className='grid grid-cols-2 gap-4 sm:grid-cols-4'>
            {metrics.map((metric) => (
              <div key={metric.label} className='rounded-xl border border-border/60 bg-card/50 p-4 text-center'>
                <p className='text-2xl font-bold text-foreground'>{metric.value}</p>
                <p className='text-xs text-muted-foreground'>{metric.label}</p>
              </div>
            ))}
          </div>
        </div>

        <div className='relative'>
          <div className='rounded-[2rem] bg-gradient-to-br from-primary/20 via-primary/5 to-background p-1'>
            <div className='rounded-[2rem] bg-card p-6 shadow-sm ring-1 ring-border/50'>
              <p className='text-sm uppercase tracking-[0.3em] text-primary'>What you get</p>
              <div className='mt-6 space-y-4'>
                {[
                  {
                    icon: <PackageSearch className='h-5 w-5' />,
                    title: 'Smart stock control',
                    description: 'Keep stock levels, reorder points, and supplier details in one dashboard.',
                  },
                  {
                    icon: <BarChart3 className='h-5 w-5' />,
                    title: 'Real-time analytics',
                    description: 'See profit & loss, sales trends, and business insights instantly.',
                  },
                  {
                    icon: <ShieldCheck className='h-5 w-5' />,
                    title: 'Secure & accessible',
                    description: 'Cloud-based with local-first performance. Works on any device.',
                  },
                ].map((feature) => (
                  <div
                    key={feature.title}
                    className='rounded-2xl border border-border/60 bg-background/80 p-4 transition hover:border-primary/30 hover:bg-background'
                  >
                    <div className='flex items-start gap-4'>
                      <div className='inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary'>
                        {feature.icon}
                      </div>
                      <div>
                        <h3 className='font-semibold text-foreground'>{feature.title}</h3>
                        <p className='text-sm text-muted-foreground'>{feature.description}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
