import { useState, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Search } from 'lucide-react';
import { useGetFinanceTransactionsQuery } from '@/app/store/features/finance/financeQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { FinanceTransactionTable } from '../components/finance/FinanceTransactionTable';
import { FinanceAdjustmentDialog } from '../components/finance/FinanceAdjustmentDialog';
import { PageLoadingState } from '@/utils/PageLoadingState';

const transactionTypes = [
  { value: '', label: 'All' },
  { value: 'sale', label: 'Sale' },
  { value: 'purchase', label: 'Purchase' },
  { value: 'expense', label: 'Expense' },
  { value: 'refund', label: 'Refund' },
  { value: 'payment_in', label: 'Payment In' },
  { value: 'payment_out', label: 'Payment Out' },
  { value: 'adjustment', label: 'Adjustment' },
];

export const AdminFinanceTransactionsPage = () => {
  const [page, setPage] = useState(1);
  const [type, setType] = useState('');
  const [category, setCategory] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [branchId, setBranchId] = useState('');
  const [search, setSearch] = useState('');

  const { data: branchesData } = useBranchesQuery();

  const queryParams = useMemo(() => {
    const params: Record<string, any> = { page };
    if (type) params.type = type;
    if (category) params.category = category;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;
    if (branchId) params.branch_id = branchId;
    if (search) params.search = search;
    return params;
  }, [page, type, category, dateFrom, dateTo, branchId, search]);

  const { data, isLoading, refetch } = useGetFinanceTransactionsQuery(queryParams);

  const records = data?.data?.data ?? data?.transactions?.data ?? [];
  const currentPage = data?.data?.current_page ?? data?.transactions?.current_page ?? 1;
  const totalPages = data?.data?.last_page ?? data?.transactions?.last_page ?? 1;
  const branches = branchesData?.branches ?? [];

  const handleFilterChange = (setter: (v: string) => void) => (value: string) => {
    setter(value);
    setPage(1);
  };

  const clearFilters = () => {
    setType('');
    setCategory('');
    setDateFrom('');
    setDateTo('');
    setBranchId('');
    setSearch('');
    setPage(1);
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader className='flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between'>
          <div className='space-y-2'>
            <CardTitle className='text-2xl'>Financial Transactions</CardTitle>
            <CardDescription className='max-w-2xl'>
              Track all financial movements across your business
            </CardDescription>
          </div>
          <FinanceAdjustmentDialog onSuccess={refetch} />
        </CardHeader>
        <CardContent className='border-t border-border/60 pt-4'>
          <div className='flex flex-wrap gap-4 items-end'>
            <div className='space-y-1'>
              <Label className='text-xs'>Type</Label>
              <Select value={type} onValueChange={handleFilterChange(setType)}>
                <SelectTrigger className='w-36'>
                  <SelectValue placeholder='All' />
                </SelectTrigger>
                <SelectContent>
                  {transactionTypes.map((t) => (
                    <SelectItem key={t.value} value={t.value}>
                      {t.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-1'>
              <Label className='text-xs'>Category</Label>
              <Input
                value={category}
                onChange={(e) => handleFilterChange(setCategory)(e.target.value)}
                placeholder='Filter category'
                className='w-40'
              />
            </div>
            <div className='space-y-1'>
              <Label className='text-xs'>From</Label>
              <Input
                type='date'
                value={dateFrom}
                onChange={(e) => handleFilterChange(setDateFrom)(e.target.value)}
                className='w-40'
              />
            </div>
            <div className='space-y-1'>
              <Label className='text-xs'>To</Label>
              <Input
                type='date'
                value={dateTo}
                onChange={(e) => handleFilterChange(setDateTo)(e.target.value)}
                className='w-40'
              />
            </div>
            <div className='space-y-1'>
              <Label className='text-xs'>Branch</Label>
              <Select value={branchId} onValueChange={handleFilterChange(setBranchId)}>
                <SelectTrigger className='w-40'>
                  <SelectValue placeholder='All branches' />
                </SelectTrigger>
                <SelectContent>
                  {branches.map((b: any) => (
                    <SelectItem key={b.id} value={String(b.id)}>
                      {b.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-1'>
              <Label className='text-xs'>Search</Label>
              <div className='relative'>
                <Search className='absolute left-2 top-2.5 h-4 w-4 text-muted-foreground' />
                <Input
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                    setPage(1);
                  }}
                  placeholder='Search...'
                  className='w-48 pl-8'
                />
              </div>
            </div>
            <Button variant='outline' size='sm' onClick={clearFilters}>
              Clear
            </Button>
          </div>
        </CardContent>
      </Card>

      {records.length === 0 && !isLoading ? (
        <Card>
          <CardContent className='py-16 text-center text-muted-foreground'>
            <p className='text-lg'>No transactions found</p>
            <p className='text-sm mt-1'>Try adjusting your filters or create a new adjustment.</p>
          </CardContent>
        </Card>
      ) : (
        <FinanceTransactionTable
          records={records}
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={setPage}
        />
      )}
    </div>
  );
};
