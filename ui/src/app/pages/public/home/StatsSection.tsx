import { PackageCheck, TrendingUp, Clock, Shield } from 'lucide-react';

const stats = [
  {
    icon: <PackageCheck className='h-6 w-6' />,
    value: '50,000+',
    label: 'Products managed',
    description: 'Items tracked across all businesses on DuukaFlow',
  },
  {
    icon: <TrendingUp className='h-6 w-6' />,
    value: '30%',
    label: 'Average savings',
    description: 'Reduction in stock losses after switching to DuukaFlow',
  },
  {
    icon: <Clock className='h-6 w-6' />,
    value: '15 min',
    label: 'Setup time',
    description: 'Get your shop up and running in minutes, not days',
  },
  {
    icon: <Shield className='h-6 w-6' />,
    value: '99.9%',
    label: 'Uptime',
    description: 'Reliable cloud infrastructure you can count on',
  },
];

export const StatsSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='rounded-[2rem] bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8 sm:p-12'>
        <div className='text-center'>
          <h2 className='text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
            Trusted by businesses across Uganda
          </h2>
          <p className='mx-auto mt-3 max-w-2xl text-muted-foreground'>
            Join hundreds of shop owners who have transformed the way they manage inventory.
          </p>
        </div>
        <div className='mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4'>
          {stats.map((stat) => (
            <div
              key={stat.label}
              className='rounded-xl border border-border/60 bg-card/60 p-6 text-center backdrop-blur-sm'
            >
              <div className='mx-auto inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary'>
                {stat.icon}
              </div>
              <p className='mt-4 text-3xl font-bold text-foreground'>{stat.value}</p>
              <p className='mt-1 font-medium text-foreground/80'>{stat.label}</p>
              <p className='mt-1 text-sm text-muted-foreground'>{stat.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
