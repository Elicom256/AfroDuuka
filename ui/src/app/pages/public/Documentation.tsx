import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SectionHeader } from './components/SectionHeader';

const docs = [
  {
    title: 'Getting started',
    description: 'Set up your shop, define categories, and add initial products quickly.',
  },
  {
    title: 'Inventory workflows',
    description: 'Record stock movement, count items, and update pricing from one dashboard.',
  },
  {
    title: 'Reports & summaries',
    description: 'Review stock health, sales trends, and reorder needs with easy visual summaries.',
  },
  {
    title: 'Mobile-ready interface',
    description: 'Use the app smoothly on smaller screens for shop floor, market and field work.',
  },
];

export const Documentation: React.FC = () => {
  return (
    <div className='container mx-auto px-4 py-10 sm:py-14'>
      <SectionHeader
        badge='Documentation'
        title='How to use DuukaFlow'
        description='A simple walkthrough for onboarding, managing stock, and keeping your inventory up to date.'
      />

      <div className='mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4'>
        {docs.map((item) => (
          <Card key={item.title} className='border border-border/70 bg-card/90 shadow-sm'>
            <CardHeader className='px-4 pt-4 pb-0'>
              <CardTitle className='text-base font-semibold text-foreground'>{item.title}</CardTitle>
            </CardHeader>
            <CardContent className='px-4 pb-4 pt-0 text-sm text-muted-foreground'>{item.description}</CardContent>
          </Card>
        ))}
      </div>

      <section className='mt-10 grid gap-8 lg:grid-cols-[0.9fr_1.1fr]'>
        <div className='space-y-6 rounded-[2rem] border border-border/70 bg-card/80 p-8 shadow-sm'>
          <h3 className='text-xl font-semibold text-foreground'>Quick start</h3>
          <ol className='space-y-4 text-sm leading-7 text-muted-foreground'>
            <li>
              <strong className='text-foreground'>1.</strong> Add your store and supply categories.
            </li>
            <li>
              <strong className='text-foreground'>2.</strong> Create products, attach stock levels, and enter cost data.
            </li>
            <li>
              <strong className='text-foreground'>3.</strong> Use inventory pages to update stock, count items, and
              manage reorder alerts.
            </li>
            <li>
              <strong className='text-foreground'>4.</strong> Use dashboard summaries to plan buying and improve
              customer service.
            </li>
          </ol>
        </div>

        <div className='rounded-[2rem] border border-border/70 bg-background/90 p-8 shadow-sm'>
          <h3 className='text-xl font-semibold text-foreground'>Best practices</h3>
          <ul className='mt-6 space-y-4 text-sm leading-7 text-muted-foreground'>
            <li>Keep item names consistent and use categories to group stock clearly.</li>
            <li>Update counts regularly so reorder decisions are based on fresh data.</li>
            <li>Review your product list weekly to remove duplicated items and keep pricing accurate.</li>
          </ul>
        </div>
      </section>
    </div>
  );
};
