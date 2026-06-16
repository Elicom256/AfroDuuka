import { Button } from '@/components/ui/button';
import { PERIODS, type ReportFilter } from '@/types';

interface PeriodFilterBarProps {
  selected: ReportFilter;
  onChange: (period: ReportFilter) => void;
}

export const PeriodFilterBar = ({ selected, onChange }: PeriodFilterBarProps) => {
  return (
    <div className='flex gap-1 bg-muted p-1 rounded-lg'>
      {PERIODS.map((p) => (
        <Button
          key={p.value}
          variant={selected === p.value ? 'default' : 'ghost'}
          size='sm'
          onClick={() => onChange(p.value)}
          className='text-xs font-medium'
        >
          {p.label}
        </Button>
      ))}
    </div>
  );
};
