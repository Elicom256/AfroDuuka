import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search } from 'lucide-react';

type ExpenseFiltersProps = {
  categories: any[];
  filters: Record<string, string>;
  search: string;
  onFilterChange: (key: string, value: string) => void;
  onSearchChange: (value: string) => void;
  onClear: () => void;
};

export const ExpenseFilters = ({ categories, filters, search, onFilterChange, onSearchChange, onClear }: ExpenseFiltersProps) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Filters</CardTitle>
      </CardHeader>
      <CardContent>
        <div className='flex flex-wrap gap-4 items-end'>
          <div className='space-y-1'>
            <Label className='text-xs'>Category</Label>
            <Select value={filters.expense_category_id ?? ''} onValueChange={(v) => onFilterChange('expense_category_id', v)}>
              <SelectTrigger className='w-40'>
                <SelectValue placeholder='All categories' />
              </SelectTrigger>
              <SelectContent>
                {categories.map((cat: any) => (
                  <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className='space-y-1'>
            <Label className='text-xs'>Status</Label>
            <Select value={filters.status ?? ''} onValueChange={(v) => onFilterChange('status', v)}>
              <SelectTrigger className='w-32'>
                <SelectValue placeholder='All statuses' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='pending'>Pending</SelectItem>
                <SelectItem value='approved'>Approved</SelectItem>
                <SelectItem value='cancelled'>Cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className='space-y-1'>
            <Label className='text-xs'>From</Label>
            <Input type='date' value={filters.date_from ?? ''} onChange={(e) => onFilterChange('date_from', e.target.value)} className='w-40' />
          </div>
          <div className='space-y-1'>
            <Label className='text-xs'>To</Label>
            <Input type='date' value={filters.date_to ?? ''} onChange={(e) => onFilterChange('date_to', e.target.value)} className='w-40' />
          </div>
          <div className='space-y-1'>
            <Label className='text-xs'>Search</Label>
            <div className='relative'>
              <Search className='absolute left-2 top-2.5 h-4 w-4 text-muted-foreground' />
              <Input value={search} onChange={(e) => onSearchChange(e.target.value)} placeholder='Search...' className='w-48 pl-8' />
            </div>
          </div>
          <Button variant='outline' size='sm' onClick={onClear}>Clear</Button>
        </div>
      </CardContent>
    </Card>
  );
};
