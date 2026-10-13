import { Lock } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type FeatureUnavailableProps = {
  feature: string;
  description?: string;
};

export const FeatureUnavailable = ({ feature, description }: FeatureUnavailableProps) => (
  <div className='mx-auto max-w-lg p-6'>
    <Card className='rounded-3xl border border-border/70 bg-card/80'>
      <CardHeader className='items-center text-center'>
        <div className='mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-muted'>
          <Lock className='h-6 w-6 text-muted-foreground' />
        </div>
        <CardTitle>{feature} is not enabled</CardTitle>
        <CardDescription>
          {description ??
            'This feature is switched off for your business. An administrator can turn it on in Settings.'}
        </CardDescription>
      </CardHeader>
      <CardContent />
    </Card>
  </div>
);
