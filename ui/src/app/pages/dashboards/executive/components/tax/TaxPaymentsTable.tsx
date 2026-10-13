import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
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
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');

  const params: any = {};
  if (categoryFilter !== 'all') params.tax_category_id = categoryFilter;
  if (from) params.from = from;
  if (to) params.to = to;

  const { data, isLoading, isFetching } = useTaxPaymentsQuery(params);
  const categories = categoriesData?.categories ?? [];

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
            <Input id='date' type='date' value={from} onChange={(e) => setFrom(e.target.value)} className='w-40' aria-label='From date' />
            <Input id='date-2' type='date' value={to} onChange={(e) => setTo(e.target.value)} className='w-40' aria-label='To date' />
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
                    <TableCell className='w-24'>
                      <EditTaxPayment payment={payment} />
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