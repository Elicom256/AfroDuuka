import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface FeatureCardProps {
  title: string;
  description: string;
  icon: React.ReactNode;
  className?: string;
}

export function FeatureCard({ title, description, icon, className }: FeatureCardProps) {
  return (
    <Card className={cn('border border-border/60 bg-background/80 shadow-sm', className)}>
      <CardHeader className='gap-3 px-4 pt-4 pb-0'>
        <div className='inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary'>
          {icon}
        </div>
        <CardTitle className='text-lg'>{title}</CardTitle>
      </CardHeader>
      <CardContent className='px-4 pb-4 pt-0'>
        <CardDescription>{description}</CardDescription>
      </CardContent>
    </Card>
  );
}
