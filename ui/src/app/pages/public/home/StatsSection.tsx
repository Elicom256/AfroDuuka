import { BarChart3, Boxes, GitBranch, MessageSquareText } from 'lucide-react';

const capabilities = [
  {
    icon: <Boxes className='h-5 w-5' />,
    label: 'Know what is on your shelves',
    description: 'Track stock, sales and reorder levels from one place.',
  },
  {
    icon: <BarChart3 className='h-5 w-5' />,
    label: 'See how the shop is doing',
    description: 'Follow sales and margins without a spreadsheet.',
  },
  {
    icon: <MessageSquareText className='h-5 w-5' />,
    label: 'Keep your team in the loop',
    description: 'Send useful stock updates through familiar channels.',
  },
  {
    icon: <GitBranch className='h-5 w-5' />,
    label: 'Grow beyond one location',
    description: 'Keep branches and their inventory connected.',
  },
];

export const StatsSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='border-y border-border py-10 sm:py-12'>
        <div className='max-w-2xl'>
          <p className='text-xs font-semibold uppercase tracking-widest text-primary'>One system, more room to grow</p>
          <h2 className='mt-3 text-3xl font-semibold text-foreground sm:text-4xl'>Built around the way retail works</h2>
          <p className='mt-3 text-muted-foreground'>
            From the counter to the next branch, keep the moving parts of your shop in view.
          </p>
        </div>
        <div className='mt-8 grid gap-x-8 sm:grid-cols-2 lg:grid-cols-4'>
          {capabilities.map((capability) => (
            <div key={capability.label} className='border-t border-border py-5'>
              <div className='flex items-center gap-3 text-primary'>
                {capability.icon}
                <h3 className='text-sm font-semibold text-foreground'>{capability.label}</h3>
              </div>
              <p className='mt-3 text-sm leading-6 text-muted-foreground'>{capability.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
