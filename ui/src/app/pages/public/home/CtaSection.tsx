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
      <div className='relative overflow-hidden rounded-3xl bg-linear-to-br from-zinc-950 via-zinc-900 to-black p-10 sm:p-16 border border-white/10'>
        {/* Background accent */}
        <div className='absolute inset-0 bg-[radial-gradient(at_top_right,#3b82f620_0%,transparent_50%)]' />
        <div className='absolute inset-0 bg-[radial-gradient(at_bottom_left,#eab30820_0%,transparent_60%)]' />

        <div className='relative z-10 mx-auto max-w-3xl text-center'>
          <h2 className='text-4xl sm:text-5xl font-bold tracking-tight text-white'>
            Ready to take control of your inventory?
          </h2>

          <p className='mx-auto mt-5 max-w-xl text-lg text-zinc-400'>
            Join hundreds of Ugandan businesses already using DuukaFlow to cut losses, save time, and scale with
            confidence.
          </p>

          <div className='mt-10 flex flex-col sm:flex-row items-center justify-center gap-4'>
            <Button size='lg' asChild className='text-base px-8'>
              <Link to='/signup'>
                Start your free trial
                <ArrowRight className='ml-2 h-5 w-5' />
              </Link>
            </Button>

            <Button
              size='lg'
              variant='outline'
              className='border-white/30 text-white hover:bg-white/10 text-base px-8'
              asChild
            >
              <Link to='/documentation'>Talk to sales</Link>
            </Button>
          </div>

          {/* Benefits */}
          <div className='mt-12 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-left'>
            {benefits.map((benefit) => (
              <div key={benefit} className='flex items-center gap-3 text-zinc-400'>
                <div className='rounded-full bg-emerald-500/20 p-1'>
                  <Check className='h-4 w-4 text-emerald-400' />
                </div>
                <span className='text-sm'>{benefit}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
