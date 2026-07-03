import { Link } from 'react-router-dom';
import { ArrowRight, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';

const benefits = [
  'Free 14-day trial — no credit card required',
  'Set up in under 15 minutes',
  'Works on any device — phone, tablet, desktop',
  'Dedicated support for Ugandan businesses',
];

export const CtaSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary via-primary/90 to-primary/80 p-8 sm:p-12'>
        <div className='absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,hsl(var(--primary-foreground)/0.15),transparent_50%)]' />
        <div className='relative z-10 text-center'>
          <h2 className='text-3xl font-bold tracking-tight text-primary-foreground sm:text-4xl'>
            Ready to transform your inventory management?
          </h2>
          <p className='mx-auto mt-4 max-w-2xl text-primary-foreground/80 sm:text-lg'>
            Join hundreds of Ugandan businesses already using DuukaFlow to save time, reduce losses, and grow with
            confidence.
          </p>

          <div className='mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row'>
            <Button size='lg' variant='secondary' asChild>
              <Link to='/signup'>
                Start your free trial
                <ArrowRight className='ml-2 h-4 w-4' />
              </Link>
            </Button>
            <Button
              size='lg'
              variant='outline'
              className='border-primary-foreground/30 text-primary-foreground hover:bg-primary-foreground/10'
              asChild
            >
              <Link to='/documentation'>Talk to sales</Link>
            </Button>
          </div>

          <div className='mt-8 flex flex-wrap justify-center gap-4'>
            {benefits.map((benefit) => (
              <div key={benefit} className='flex items-center gap-2 text-sm text-primary-foreground/80'>
                <Check className='h-4 w-4 text-primary-foreground/60' />
                <span>{benefit}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
