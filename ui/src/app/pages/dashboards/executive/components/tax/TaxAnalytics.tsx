import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Percent, ReceiptText, Wallet2 } from 'lucide-react';
import { useTaxPaymentAnalyticsQuery } from '@/app/store/features/business/tax/taxQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PageLoadingState } from '@/utils/PageLoadingState';

const toIsoDate = (d: Date) => d.toISOString().slice(0, 10);

const monthRange = (offset = 0) => {
  const now = new Date();
  return {
    from: toIsoDate(new Date(now.getFullYear(), now.getMonth() - offset, 1)),
    to: toIsoDate(now),
  };
};

const yearRange = () => {
  const now = new Date();
  return { from: toIsoDate(new Date(now.getFullYear(), 0, 1)), to: toIsoDate(now) };
};

export const TaxAnalytics = () => {
  const { currencySymbol } = useCurrency();
  const [period, setPeriod] = useState('this_month');
  const [customFrom, setCustomFrom] = useState('');
  const [customTo, setCustomTo] = useState('');

  const resolveRange = () => {
    if (period === 'last_month') return monthRange(1);
    if (period === 'this_year') return yearRange();
    if (period === 'custom') {
      return {
        from: customFrom || yearRange().from,
        to: customTo || toIsoDate(new Date()),
      };
    }
    return monthRange();
  };

  const params = resolveRange();
  const { data, isLoading, isFetching } = useTaxPaymentAnalyticsQuery(params);

  const analytics = data?.analytics ?? data ?? {};
  const totalPaid = Number(analytics?.total_paid ?? 0);
  const count = Number(analytics?.total_count ?? 0);
  const average = count > 0 ? totalPaid / count : 0;
  const byCategory = analytics?.by_category ?? [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-4'>
      <Card>
        <CardHeader>
          <CardTitle>Analytics</CardTitle>
        </CardHeader>
        <CardContent className='flex flex-wrap items-end gap-4'>
          <div className='space-y-2'>
            <Label htmlFor='period'>Period</Label>
            <Select value={period} onValueChange={setPeriod}>
              <SelectTrigger id='period' className='w-48'>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='this_month'>This month</SelectItem>
                <SelectItem value='last_month'>Last 30 days</SelectItem>
                <SelectItem value='this_year'>This year</SelectItem>
                <SelectItem value='custom'>Custom range</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {period === 'custom' && (
            <>
              <div className='space-y-2'>
                <Label htmlFor='from'>From</Label>
                <Input id='from' type='date' value={customFrom} onChange={(e) => setCustomFrom(e.target.value)} className='w-40' />
              </div>
              <div className='space-y-2'>
                <Label htmlFor='to'>To</Label>
                <Input id='to' type='date' value={customTo} onChange={(e) => setCustomTo(e.target.value)} className='w-40' />
              </div>
            </>
          )}
          <div className='ml-auto flex items-center gap-2 text-sm text-muted-foreground'>
            <span>{params.from}</span>
            <span>→</span>
            <span>{params.to}</span>
          </div>
          {isFetching && <span className='text-sm text-muted-foreground'>Refreshing…</span>}
        </CardContent>
      </Card>

      <div className='grid gap-4 sm:grid-cols-3'>
        <Card>
          <CardContent className='flex items-center gap-4 py-4'>
            <div className='rounded-lg bg-primary/10 p-2'>
              <Wallet2 className='h-5 w-5 text-primary' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Total Paid</p>
              <p className='text-2xl font-bold'>{currencySymbol}{totalPaid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className='flex items-center gap-4 py-4'>
            <div className='rounded-lg bg-primary/10 p-2'>
              <ReceiptText className='h-5 w-5 text-primary' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Payments Made</p>
              <p className='text-2xl font-bold'>{count}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className='flex items-center gap-4 py-4'>
            <div className='rounded-lg bg-primary/10 p-2'>
              <Percent className='h-5 w-5 text-primary' />
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Average Payment</p>
              <p className='text-2xl font-bold'>{currencySymbol}{average.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Totals by Tax Category</CardTitle>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Tax Category</TableHead>
                <TableHead className='text-right'>Total Paid</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {byCategory.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={2} className='py-10 text-center text-muted-foreground'>No payment data for the selected period.</TableCell>
                </TableRow>
              ) : (
                byCategory.map((row: any) => (
                  <TableRow key={row.tax_category_id}>
                    <TableCell className='font-medium'>{row.name ?? `#${row.tax_category_id}`}</TableCell>
                    <TableCell className='text-right'>{currencySymbol}{Number(row.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
};