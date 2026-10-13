import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { deferNamed } from '@/components/defer';
import type { LandingPeriod } from './landingPeriods';

/**
 * The two dashboard widgets that bring chart.js with them, deferred behind a
 * suspense boundary that mirrors each component's own loading skeleton.
 *
 * `SalesStatCards` is mostly stat cards but imports `Line` from react-chartjs-2
 * for its sparklines, so it drags the whole chart library in regardless.
 */
type PeriodProps = { period: LandingPeriod };

export const SalesStatCards = deferNamed<PeriodProps>(
  () => import('./sales-purchases/SalesStatCards'),
  'SalesStatCards',
  <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
    {Array.from({ length: 4 }).map((_, i) => (
      <Skeleton key={i} className='h-20 rounded-3xl' />
    ))}
  </div>
);

export const RevenueChart = deferNamed<PeriodProps>(
  () => import('./overview/RevenueChart'),
  'RevenueChart',
  <Card>
    <CardHeader className='pb-3'>
      <Skeleton className='h-5 w-32' />
    </CardHeader>
    <CardContent>
      <Skeleton className='h-72 w-full' />
    </CardContent>
  </Card>
);
