import type { ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface AuthLayoutProps {
  title: string;
  description: string;
  children: ReactNode;
}

export const AuthLayout = ({ title, description, children }: AuthLayoutProps) => {
  return (
    <div className='grid min-h-[calc(100vh-8rem)] place-items-center bg-background px-4 py-12'>
      <Card className='w-full max-w-md border border-border/70 bg-card/95 shadow-xl'>
        <CardHeader className='space-y-1 px-6 pt-6'>
          <CardTitle className='text-3xl font-semibold'>{title}</CardTitle>
          <CardDescription className='text-sm text-muted-foreground'>
            {description}
          </CardDescription>
        </CardHeader>
        <CardContent className='px-6 pb-6 pt-0'>{children}</CardContent>
      </Card>
    </div>
  );
};
