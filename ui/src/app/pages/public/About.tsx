import { CheckCircle2, HeartHandshake, ShieldCheck } from 'lucide-react';
import { FeatureCard } from './components/FeatureCard';
import { SectionHeader } from './components/SectionHeader';

const values = [
  {
    icon: <ShieldCheck className='h-5 w-5' />,
    title: 'Reliable workflows',
    description: 'Track inventory movement with clear actions, item histories, and intuitive product pages.',
  },
  {
    icon: <HeartHandshake className='h-5 w-5' />,
    title: 'Built for people',
    description: 'A simple UI and strong mobile-first experience help teams use the app every day.',
  },
  {
    icon: <CheckCircle2 className='h-5 w-5' />,
    title: 'Local-first design',
    description: 'A Ugandan-made system that stays lightweight while still feeling polished and modern.',
  },
];

export const About: React.FC = () => {
  return (
    <div className='container mx-auto px-4 py-10 sm:py-14'>
      <SectionHeader
        badge='About DuukaFlow'
        title='A modern inventory experience for every store owner'
        description='We build tools that keep inventory accurate, sales smooth, and daily stock management faster.'
      />

      <div className='mt-10 grid gap-8 lg:grid-cols-[0.9fr_1.1fr]'>
        <div className='space-y-6 rounded-[2rem] border border-border/70 bg-card/80 p-8 shadow-sm'>
          <p className='text-base leading-7 text-muted-foreground'>
            DuukaFlow is a local inventory management interface that prioritizes clarity, speed, and strong UX. It was
            created to support retail teams from kiosks to shops, while still being easy to customize as the business
            grows.
          </p>
          <div className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-3xl bg-background/90 p-6'>
              <p className='text-sm uppercase tracking-[0.3em] text-muted-foreground'>Mission</p>
              <p className='mt-3 text-2xl font-semibold text-foreground'>Make stock simple for every store.</p>
            </div>
            <div className='rounded-3xl bg-background/90 p-6'>
              <p className='text-sm uppercase tracking-[0.3em] text-muted-foreground'>Vision</p>
              <p className='mt-3 text-2xl font-semibold text-foreground'>
                A modern retail tool that grows with Ugandan businesses.
              </p>
            </div>
          </div>
        </div>

        <div className='grid gap-4'>
          {values.map((value) => (
            <FeatureCard key={value.title} icon={value.icon} title={value.title} description={value.description} />
          ))}
        </div>
      </div>

      <section className='mt-10 rounded-[2rem] border border-border/70 bg-card/80 p-8 shadow-sm'>
        <h3 className='text-xl font-semibold text-foreground'>What we care about</h3>
        <div className='mt-6 grid gap-6 md:grid-cols-3'>
          <div className='rounded-3xl bg-background/90 p-6'>
            <p className='text-sm uppercase tracking-[0.24em] text-muted-foreground'>Experience</p>
            <p className='mt-3 text-base leading-7 text-muted-foreground'>
              A clean, light interface that helps new users move quickly and keeps product pages easy to scan.
            </p>
          </div>
          <div className='rounded-3xl bg-background/90 p-6'>
            <p className='text-sm uppercase tracking-[0.24em] text-muted-foreground'>Consistency</p>
            <p className='mt-3 text-base leading-7 text-muted-foreground'>
              Use the same modern styling across pages so every screen feels polished and predictable.
            </p>
          </div>
          <div className='rounded-3xl bg-background/90 p-6'>
            <p className='text-sm uppercase tracking-[0.24em] text-muted-foreground'>Accessibility</p>
            <p className='mt-3 text-base leading-7 text-muted-foreground'>
              Responsive layouts and readable visuals that work on both large desktops and small mobile phones.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
};
