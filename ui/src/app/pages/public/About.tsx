import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SectionHeader } from './components/SectionHeader';
import { Target, Eye, Zap, Shield, Globe, Users } from 'lucide-react';

const stats = [
  { value: '500+', label: 'Products supported' },
  { value: '99.9%', label: 'Uptime target' },
  { value: '<2s', label: 'Page load time' },
  { value: '24/7', label: 'Support available' },
];

const values = [
  {
    icon: <Target className='h-5 w-5' />,
    title: 'Mission-driven',
    description: 'We exist to make inventory management effortless for every African business, from corner shops to multi-branch retail chains.',
  },
  {
    icon: <Zap className='h-5 w-5' />,
    title: 'Speed first',
    description: 'Every feature is designed to save time. No clutter, no complexity — just the tools you need to run your business.',
  },
  {
    icon: <Shield className='h-5 w-5' />,
    title: 'Trust & security',
    description: 'Your business data is encrypted, backed up, and never shared. We build trust through transparency and reliability.',
  },
  {
    icon: <Globe className='h-5 w-5' />,
    title: 'Built for Africa',
    description: 'Designed for local payment methods, offline resilience, and the unique challenges of African retail environments.',
  },
  {
    icon: <Users className='h-5 w-5' />,
    title: 'Customer-centric',
    description: 'We listen to our users and iterate fast. Every feature is shaped by real feedback from real businesses.',
  },
  {
    icon: <Eye className='h-5 w-5' />,
    title: 'Continuous improvement',
    description: 'We ship updates weekly, fix bugs fast, and constantly refine the experience based on how teams actually work.',
  },
];

export const About: React.FC = () => {
  return (
    <div className='mx-auto max-w-6xl px-4 py-16 sm:py-24'>
      <SectionHeader
        badge='About Us'
        title='We are building the future of African retail'
        description='DuukaFlow is a modern inventory management platform built for African businesses. We combine powerful features with an intuitive experience to help you sell more, stock smarter, and grow faster.'
      />

      <div className='mt-16 grid gap-8 lg:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card/80 p-8 shadow-sm'>
          <CardContent className='space-y-6 p-0'>
            <div className='space-y-4'>
              <Badge variant='secondary' className='text-xs font-medium'>Our Story</Badge>
              <h2 className='text-2xl font-bold tracking-tight text-foreground sm:text-3xl'>
                From paper ledgers to digital excellence
              </h2>
              <p className='text-base leading-7 text-muted-foreground'>
                DuukaFlow started with a simple observation: African businesses deserve world-class software built for their reality. We saw shop owners struggling with stockouts, manual bookkeeping, and systems designed for markets with perfect infrastructure.
              </p>
              <p className='text-base leading-7 text-muted-foreground'>
                So we built a platform that works offline, accepts mobile money, and speaks your language — literally and figuratively. Today, we serve businesses across Uganda and beyond, helping them modernize operations and compete in the digital economy.
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className='rounded-3xl border border-border/70 bg-card/80 p-8 shadow-sm'>
          <CardContent className='space-y-6 p-0'>
            <div className='space-y-4'>
              <Badge variant='secondary' className='text-xs font-medium'>Our Mission</Badge>
              <h2 className='text-2xl font-bold tracking-tight text-foreground sm:text-3xl'>
                Empowering every business to thrive
              </h2>
              <p className='text-base leading-7 text-muted-foreground'>
                We believe every business owner deserves tools that work as hard as they do. Our mission is to make professional inventory management accessible, affordable, and effortless — so you can focus on growing your business, not fighting your software.
              </p>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              {stats.map((stat) => (
                <div key={stat.label} className='rounded-2xl bg-background/90 p-4 text-center'>
                  <p className='text-2xl font-bold text-primary'>{stat.value}</p>
                  <p className='mt-1 text-xs text-muted-foreground'>{stat.label}</p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <section className='mt-16'>
        <div className='text-center'>
          <Badge variant='secondary' className='text-xs font-medium'>Our Values</Badge>
          <h2 className='mt-4 text-2xl font-bold tracking-tight text-foreground sm:text-3xl'>
            What drives everything we build
          </h2>
        </div>
        <div className='mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
          {values.map((value) => (
            <Card key={value.title} className='rounded-2xl border border-border/70 bg-card/80 p-6 shadow-sm transition-all hover:border-primary/30 hover:shadow-md'>
              <CardContent className='space-y-4 p-0'>
                <div className='flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary'>
                  {value.icon}
                </div>
                <h3 className='text-lg font-semibold text-foreground'>{value.title}</h3>
                <p className='text-sm leading-6 text-muted-foreground'>{value.description}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      <section className='mt-16 rounded-3xl border border-border/70 bg-gradient-to-br from-primary/5 to-transparent p-8 text-center sm:p-12'>
        <h2 className='text-2xl font-bold tracking-tight text-foreground sm:text-3xl'>
          Ready to transform your business?
        </h2>
        <p className='mx-auto mt-4 max-w-2xl text-base leading-7 text-muted-foreground'>
          Join thousands of businesses already using DuukaFlow to manage inventory, track sales, and grow with confidence.
        </p>
        <div className='mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row'>
          <a
            href='/pricing'
            className='inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition-all hover:bg-primary/90'
          >
            View Pricing
          </a>
          <a
            href='/documentation'
            className='inline-flex items-center justify-center rounded-xl border border-border bg-background px-6 py-3 text-sm font-semibold text-foreground transition-all hover:bg-muted'
          >
            Read Documentation
          </a>
        </div>
      </section>
    </div>
  );
};
