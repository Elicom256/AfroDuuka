import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const OperationsPageShell = ({
  title,
  description,
  children,
  actions,
}: {
  title: string;
  description: string;
  children: ReactNode;
  actions?: ReactNode;
}) => (
  <Card className='rounded-3xl border border-border/70 bg-card p-6'>
    <CardHeader className='flex flex-row items-center justify-between'>
      <div>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </div>
      {actions && <div className='flex gap-2'>{actions}</div>}
    </CardHeader>
    <CardContent className='space-y-4'>{children}</CardContent>
  </Card>
);

export const SectionCard = ({ title, value, icon }: { title: string; value: string | number; icon: ReactNode }) => (
  <div className='rounded-3xl border border-border/70 bg-muted p-6'>
    <div className='flex items-start justify-between gap-4'>
      <div>
        <p className='text-sm font-medium text-muted-foreground'>{title}</p>
        <p className='mt-3 text-4xl font-semibold'>{value}</p>
      </div>
      <div className='flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary'>{icon}</div>
    </div>
  </div>
);
