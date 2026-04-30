import { cn } from '@/lib/utils';

interface SectionHeaderProps {
  title: string;
  description: string;
  badge?: string;
  className?: string;
}

export function SectionHeader({ title, description, badge, className }: SectionHeaderProps) {
  return (
    <div className={cn('space-y-4', className)}>
      {badge ? (
        <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary'>
          {badge}
        </span>
      ) : null}
      <div>
        <h2 className='font-heading text-3xl font-semibold tracking-tight text-foreground sm:text-4xl'>{title}</h2>
        <p className='max-w-2xl text-muted-foreground sm:text-lg'>{description}</p>
      </div>
    </div>
  );
}
