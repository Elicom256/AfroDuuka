import { Link } from 'react-router-dom';
import { ArrowDownRight, ArrowRight, ArrowUpRight, Bell, ChevronDown, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';

const products = [
  { name: 'Sunlight soap 1kg', code: 'HOM-024', stock: '12 units', status: 'Reorder soon' },
  { name: 'Blue Band 500g', code: 'FOD-108', stock: '38 units', status: 'In stock' },
  { name: 'Kakira sugar 2kg', code: 'FOD-016', stock: '6 units', status: 'Low stock' },
];

export const HeroSection = () => {
  return (
    <section className='py-12 sm:py-16 lg:py-20'>
      <div className='grid items-center gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14'>
        <div className='space-y-7'>
          <p className='flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.16em] text-primary'>
            <span className='h-px w-8 bg-primary' />
            Business software, built for Africa
          </p>
          <h1 className='max-w-xl text-4xl font-semibold leading-[1.08] text-foreground sm:text-5xl lg:text-[3.5rem]'>
            Your shop is growing.{' '}
            <span className='text-primary'>Your stock system should too.</span>
          </h1>
          <p className='max-w-lg text-base leading-7 text-muted-foreground sm:text-lg'>
            Sales, stock, suppliers and branches in one clear view. DuukaFlow gives growing African retailers the
            control to run today&apos;s shop and plan for the next one.
          </p>

          <div className='flex flex-col gap-3 sm:flex-row sm:items-center'>
            <Button size='lg' className='h-12 px-5' asChild>
              <Link to='/signup'>
                Get started
                <ArrowRight className='ml-2 h-4 w-4' />
              </Link>
            </Button>
            <Button size='lg' variant='ghost' className='h-12 px-5 text-foreground' asChild>
              <Link to='/pricing'>View pricing</Link>
            </Button>
          </div>

          <div className='flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-border pt-5 text-sm text-muted-foreground'>
            <span>Made for independent shops</span>
            <span className='hidden h-1 w-1 rounded-full bg-primary/50 sm:block' />
            <span>Ready for multiple branches</span>
          </div>
        </div>

        <div className='min-w-0 border border-border bg-card shadow-[0_24px_70px_-42px_rgba(24,52,35,0.45)]'>
          <div className='flex items-center justify-between border-b border-border px-4 py-3 sm:px-5'>
            <div className='flex items-center gap-2.5'>
              <img src='/afroduuka.png' alt='' className='h-8 w-8 object-contain' />
              <div>
                <p className='text-sm font-semibold text-foreground'>DuukaFlow</p>
                <p className='text-[11px] text-muted-foreground'>Retail operations</p>
              </div>
            </div>
            <div className='flex items-center gap-2'>
              <span className='hidden text-xs text-muted-foreground sm:inline'>Kampala Central</span>
              <button aria-label='Notifications' className='relative grid h-9 w-9 place-items-center border border-border text-muted-foreground'>
                <Bell className='h-4 w-4' />
                <span className='absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-amber-500' />
              </button>
              <span className='grid h-8 w-8 place-items-center bg-secondary text-xs font-semibold text-foreground'>JM</span>
            </div>
          </div>

          <div className='grid sm:grid-cols-[9.5rem_minmax(0,1fr)]'>
            <aside className='hidden border-r border-border bg-[#f2f4ed] p-3 sm:block'>
              <p className='px-2 pb-3 pt-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground'>Workspace</p>
              <div className='space-y-1 text-xs'>
                <p className='bg-primary px-2.5 py-2 font-medium text-primary-foreground'>Overview</p>
                <p className='px-2.5 py-2 text-muted-foreground'>Inventory</p>
                <p className='px-2.5 py-2 text-muted-foreground'>Sales</p>
                <p className='px-2.5 py-2 text-muted-foreground'>Suppliers</p>
                <p className='px-2.5 py-2 text-muted-foreground'>Branches</p>
              </div>
              <div className='mt-8 border-t border-border px-2 pt-3'>
                <p className='text-[10px] uppercase tracking-[0.1em] text-muted-foreground'>Store status</p>
                <p className='mt-2 flex items-center gap-2 text-xs font-medium text-foreground'>
                  <span className='h-2 w-2 rounded-full bg-primary' /> Synced just now
                </p>
              </div>
            </aside>

            <div className='min-w-0 p-4 sm:p-5'>
              <div className='mb-4 flex flex-wrap items-center justify-between gap-3'>
                <div>
                  <p className='text-base font-semibold text-foreground'>Good morning, James</p>
                  <p className='mt-0.5 text-xs text-muted-foreground'>Here&apos;s what&apos;s happening at your shop today.</p>
                </div>
                <button className='flex items-center gap-2 border border-border px-3 py-2 text-xs text-foreground'>
                  This week <ChevronDown className='h-3.5 w-3.5' />
                </button>
              </div>

              <div className='mb-4 grid grid-cols-3 gap-2 sm:gap-3'>
                <div className='border border-border p-3'>
                  <p className='text-[10px] text-muted-foreground sm:text-xs'>Sales today</p>
                  <p className='mt-1 text-sm font-semibold text-foreground sm:text-lg'>UGX 2.48m</p>
                  <p className='mt-1 flex items-center gap-1 text-[10px] text-primary'><ArrowUpRight className='h-3 w-3' /> 8.2%</p>
                </div>
                <div className='border border-border p-3'>
                  <p className='text-[10px] text-muted-foreground sm:text-xs'>Stock value</p>
                  <p className='mt-1 text-sm font-semibold text-foreground sm:text-lg'>UGX 18.6m</p>
                  <p className='mt-1 text-[10px] text-muted-foreground'>Across 248 items</p>
                </div>
                <div className='border border-border p-3'>
                  <p className='text-[10px] text-muted-foreground sm:text-xs'>Needs attention</p>
                  <p className='mt-1 text-sm font-semibold text-foreground sm:text-lg'>6 items</p>
                  <p className='mt-1 flex items-center gap-1 text-[10px] text-amber-700'><ArrowDownRight className='h-3 w-3' /> Low stock</p>
                </div>
              </div>

              <div className='grid gap-3 md:grid-cols-[1.1fr_0.9fr]'>
                <div className='border border-border p-3 sm:p-4'>
                  <div className='flex items-start justify-between gap-2'>
                    <div>
                      <p className='text-xs font-semibold text-foreground'>Sales overview</p>
                      <p className='mt-1 text-[10px] text-muted-foreground'>Weekly sales · UGX</p>
                    </div>
                    <button aria-label='Search sales' className='grid h-7 w-7 place-items-center text-muted-foreground'><Search className='h-3.5 w-3.5' /></button>
                  </div>
                  <div className='mt-4 flex h-24 items-end gap-2 border-b border-border px-1'>
                    {[35, 53, 42, 70, 54, 88, 66].map((height, index) => (
                      <div key={index} className='flex flex-1 flex-col justify-end'>
                        <span className={`w-full ${index === 5 ? 'bg-primary' : 'bg-primary/20'}`} style={{ height: `${height}%` }} />
                      </div>
                    ))}
                  </div>
                  <div className='mt-2 flex justify-between text-[9px] text-muted-foreground'>
                    <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                  </div>
                </div>

                <div className='border border-border p-3 sm:p-4'>
                  <div className='flex items-center justify-between'>
                    <p className='text-xs font-semibold text-foreground'>Stock to watch</p>
                    <span className='text-[10px] text-primary'>View all</span>
                  </div>
                  <div className='mt-2 divide-y divide-border'>
                    {products.map((product) => (
                      <div key={product.code} className='flex items-center justify-between gap-2 py-2'>
                        <div className='min-w-0'>
                          <p className='truncate text-[10px] font-medium text-foreground'>{product.name}</p>
                          <p className='text-[9px] text-muted-foreground'>{product.code} · {product.stock}</p>
                        </div>
                        <span className={`shrink-0 text-[9px] ${product.status === 'In stock' ? 'text-primary' : 'text-amber-700'}`}>
                          {product.status}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <p className='mt-3 text-right text-[9px] text-muted-foreground'>Illustrative sample data</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
