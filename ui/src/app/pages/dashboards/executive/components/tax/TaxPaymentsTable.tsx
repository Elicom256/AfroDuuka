import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2 } from 'lucide-react';
import {
  useDeleteTaxPaymentMutation,
  useTaxCategoriesQuery,
  useTaxPaymentsQuery,
} from '@/app/store/features/business/tax/taxQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddTaxPayment } from './AddTaxPayment';
import { EditTaxPayment } from './EditTaxPayment';

export const TaxPaymentsTable = () => {
  const { currencySymbol } = useCurrency();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [deletePayment] = useDeleteTaxPaymentMutation();
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');

  const params: any = {};
  if (categoryFilter !== 'all') params.tax_category_id = categoryFilter;
  if (from) params.from = from;
  if (to) params.to = to;

  const { data, isLoading, isFetching } = useTaxPaymentsQuery(params);
  const categories = categoriesData?.categories ?? [];

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this payment record?')) return;
    try {
      const res = await deletePayment(id).unwrap();
      toast.success(res?.message || 'Tax payment deleted');
    } catch {
      toast.error('Failed to delete tax payment');
    }
  };

  if (isLoading) return <PageLoadingState />;
  const payments = data?.payments ?? [];

  return (
    <Card>
      <CardHeader className='flex flex-col gap-4'>
        <div className='flex items-center justify-between'>
          <CardTitle>Tax Payments</CardTitle>
          <div className='flex items-center gap-2'>
            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
              <SelectTrigger className='w-48'>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='all'>All categories</SelectItem>
                {categories.map((category: any) => (
                  <SelectItem key={category.id} value={String(category.id)}>{category.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input type='date' value={from} onChange={(e) => setFrom(e.target.value)} className='w-40' aria-label='From date' />
            <Input type='date' value={to} onChange={(e) => setTo(e.target.value)} className='w-40' aria-label='To date' />
            <AddTaxPayment />
          </div>
        </div>
        {isFetching && <p className='text-sm text-muted-foreground'>Refreshing…</p>}
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Reference</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Payment Date</TableHead>
              <TableHead>Tax Period</TableHead>
              <TableHead>Notes</TableHead>
              <TableHead className='w-24'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {payments.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className='py-10 text-center text-muted-foreground'>No tax payments found.</TableCell>
              </TableRow>
            ) : (
              payments.map((payment: any) => {
                const category = categories.find((c: any) => c.id === payment.tax_category_id);
                return (
                  <TableRow key={payment.id}>
                    <TableCell className='font-medium'>{payment.reference || '—'}</TableCell>
                    <TableCell>{category?.name ?? `#${payment.tax_category_id}`}</TableCell>
                    <TableCell>{currencySymbol}{Number(payment.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</TableCell>
                    <TableCell>{payment.payment_date}</TableCell>
                    <TableCell className='text-muted-foreground'>
                      {(payment.tax_period_start || '—')}{payment.tax_period_start && payment.tax_period_end ? ` → ${payment.tax_period_end}` : ''}
                    </TableCell>
                    <TableCell className='text-muted-foreground'>{payment.notes || '—'}</TableCell>
                    <TableCell>
                      <div className='flex gap-2'>
                        <EditTaxPayment payment={payment} />
                        <Button variant='ghost' size='icon' onClick={() => handleDelete(payment.id)}>
                          <Trash2 className='h-4 w-4' />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};