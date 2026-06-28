import { type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';

interface StatsCardProps {
  title: string;
  value: string | number;
  icon: LucideIcon;
  description?: string;
  trend?: {
    value: string;
    positive: boolean;
  };
  isLoading?: boolean;
  iconClassName?: string;
}

export const StatsCard = ({
  title,
  value,
  icon: Icon,
  description,
  trend,
  isLoading,
  iconClassName,
}: StatsCardProps) => {
  
  return (
    <div className='rounded-3xl border border-border/70 bg-card p-6 transition-all hover:shadow-md'>
      <div className='flex items-start justify-between gap-4'>
        <div className='space-y-1.5'>
          <p className='text-sm font-medium text-muted-foreground'>{title}</p>
          {isLoading ? (
            <Skeleton className='h-9 w-20 rounded-md' />
          ) : (
            <p className='text-4xl font-semibold tracking-tight'>{value}</p>
          )}
          {description && (
            <p className='text-xs text-muted-foreground'>{description}</p>
          )}
          {trend && (
            <p
              className={cn(
                'text-xs font-medium',
                trend.positive
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : 'text-red-600 dark:text-red-400',
              )}
            >
              {trend.positive ? '↑' : '↓'} {trend.value}
            </p>
          )}
        </div>
        <div
          className={cn(
            'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl',
            iconClassName ?? 'bg-primary/10 text-primary',
          )}
        >
          <Icon className='h-5 w-5' />
        </div>
      </div>
    </div>
  );
};
