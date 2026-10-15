import { Lock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type FeatureLockedCardProps = {
  title: string;
  description?: string;
};

/**
 * The widget-sized stand-in for a card whose data comes from a switched-off
 * feature.
 *
 * `FeatureUnavailable` is a full page and would leave a torn hole in a dashboard
 * grid. This keeps the card's frame and title, so the layout does not reflow when
 * the feature is on, and explains the state in place of the fetch error the card
 * would otherwise show. The queries behind the card are skipped by their callers,
 * never fired and refused, so nothing reaches the api to be turned into a 403.
 */
export const FeatureLockedCard = ({ title, description }: FeatureLockedCardProps) => (
  <Card className='h-full'>
    <CardHeader className='pb-3'>
      <CardTitle className='text-sm'>{title}</CardTitle>
    </CardHeader>
    <CardContent>
      <div className='flex flex-col items-center gap-2 py-6 text-center'>
        <div className='flex h-10 w-10 items-center justify-center rounded-full bg-muted'>
          <Lock className='h-5 w-5 text-muted-foreground' aria-hidden='true' />
        </div>
        <p className='text-sm font-medium'>Reports is not enabled</p>
        <p className='text-xs text-muted-foreground'>
          {description ?? 'An administrator can turn it on in Settings.'}
        </p>
      </div>
    </CardContent>
  </Card>
);
