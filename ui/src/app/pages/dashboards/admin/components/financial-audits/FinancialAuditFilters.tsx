import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, X } from 'lucide-react';

type Props = {
  branches: any[];
  filters: Record<string, string>;
  search: string;
  onFilterChange: (key: string, value: string) => void;
  onSearchChange: (value: string) => void;
  onClear: () => void;
};

export const FinancialAuditFilters = ({ branches, filters, search, onFilterChange, onSearchChange, onClear }: Props) => (
  <div className='flex flex-wrap items-center gap-3'>
    <div className='relative flex-1 min-w-[200px] max-w-sm'>
      <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
      <Input
        placeholder='Search audit number...'
        value={search}
        onChange={(e) => onSearchChange(e.target.value)}
        className='pl-9 rounded-2xl'
      />
    </div>
    <Select value={filters.business_branch_id || ''} onValueChange={(v) => onFilterChange('business_branch_id', v)}>
      <SelectTrigger className='w-[180px] rounded-2xl'>
        <SelectValue placeholder='All branches' />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value=' '>All branches</SelectItem>
        {branches.map((b: any) => (
          <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
        ))}
      </SelectContent>
    </Select>
    <Select value={filters.status || ''} onValueChange={(v) => onFilterChange('status', v)}>
      <SelectTrigger className='w-[160px] rounded-2xl'>
        <SelectValue placeholder='All statuses' />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value=' '>All statuses</SelectItem>
        <SelectItem value='draft'>Draft</SelectItem>
        <SelectItem value='in_progress'>In Progress</SelectItem>
        <SelectItem value='completed'>Completed</SelectItem>
        <SelectItem value='approved'>Approved</SelectItem>
        <SelectItem value='cancelled'>Cancelled</SelectItem>
      </SelectContent>
    </Select>
    <Input
      type='date'
      value={filters.date_from || ''}
      onChange={(e) => onFilterChange('date_from', e.target.value)}
      className='w-[160px] rounded-2xl'
    />
    <Input
      type='date'
      value={filters.date_to || ''}
      onChange={(e) => onFilterChange('date_to', e.target.value)}
      className='w-[160px] rounded-2xl'
    />
    {(filters.business_branch_id || filters.status || filters.date_from || filters.date_to || search) && (
      <Button variant='ghost' size='sm' onClick={onClear} className='rounded-2xl'>
        <X className='h-4 w-4 mr-1' /> Clear
      </Button>
    )}
  </div>
);
