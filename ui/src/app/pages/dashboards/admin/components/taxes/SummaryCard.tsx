import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const SummaryCard = ({ title, description, value }: { title: string; description: string; value: string | number }) => (
  <Card className='rounded-3xl border border-border/70 bg-card p-2'>
    <CardHeader>
      <CardTitle>{title ?? 'test'}</CardTitle>
      <CardDescription>{description ?? 'description'}</CardDescription>
    </CardHeader>
    <CardContent>
      <p className='text-2xl font-semibold'>{value ?? 7}</p>
    </CardContent>
  </Card>
);