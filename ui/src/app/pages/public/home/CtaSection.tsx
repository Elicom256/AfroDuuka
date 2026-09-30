import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

export const CtaSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='bg-[#244c35] px-6 py-10 sm:px-12 sm:py-14'>
        <div className='mx-auto max-w-3xl'>
          <p className='text-xs font-semibold uppercase tracking-widest text-[#d5df9a]'>
            A clearer way to run your shop
          </p>
          <h2 className='mt-4 text-3xl font-semibold text-white sm:text-4xl'>
            Ready to take control of your inventory?
          </h2>

          <p className='mt-4 max-w-xl text-base leading-7 text-white/75'>
            Bring your products, sales and branches into one system, then grow at your own pace.
          </p>

          <div className='mt-7 flex flex-col gap-3 sm:flex-row'>
            <Button size='lg' asChild className='bg-[#e1e89f] px-6 text-[#203d2c] hover:bg-[#edf1c0]'>
              <Link to='/signup'>
                Get started
                <ArrowRight className='ml-2 h-5 w-5' />
              </Link>
            </Button>

            <Button size='lg' variant='ghost' className='px-6 text-white hover:bg-white/10 hover:text-white' asChild>
              <Link to='/pricing'>Compare plans</Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
};
