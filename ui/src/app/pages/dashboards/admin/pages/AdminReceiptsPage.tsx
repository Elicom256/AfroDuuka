import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useReceiptsQuery } from '@/app/store/features/branch/receipts/receiptsQuery';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { PaginationComponent } from '@/app/utils/Pagination';
import { Search, Filter, Eye, Download, Receipt } from 'lucide-react';
import { Link } from 'react-router-dom';
import { format } from 'date-fns';

export const AdminReceiptsPage = () => {
  const { currency } = useCurrency();
  const [search, setSearch] = useState('');
  const [customerId, setCustomerId] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [status, setStatus] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [currentPage, setCurrentPage] = useState(1);

  const params: Record<string, any> = { page: currentPage, per_page: 15 };
  if (search) params.search = search;
  if (customerId) params.customer_id = customerId;
  if (paymentMethod) params.payment_method = paymentMethod;
  if (status) params.status = status;
  if (dateFrom) params.date_from = dateFrom;
  if (dateTo) params.date_to = dateTo;

  const { data, isLoading } = useReceiptsQuery(params);
  const { data: customersData } = useCustomersQuery();

  if (isLoading) return <PageLoadingState />;

  const customers = customersData?.customers || [];
  const receipts = data?.receipts?.data || data?.receipts || [];
  const pagination = data?.receipts;

  const totalPages = pagination?.last_page || 1;

  const statusVariant = (s: string) => {
    switch (s) {
      case 'completed': return 'success';
      case 'refunded': return 'warning';
      case 'voided': return 'destructive';
      default: return 'secondary';
    }
  };

  const handleFilter = () => {
    setCurrentPage(1);
  };

  const handleClear = () => {
    setSearch('');
    setCustomerId('');
    setPaymentMethod('');
    setStatus('');
    setDateFrom('');
    setDateTo('');
    setCurrentPage(1);
  };

  const baseUrl = import.meta.env.VITE_BASE_URL || 'http://localhost/api';

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card p-2'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Receipt className='h-5 w-5' />
            Receipts
          </CardTitle>
          <CardDescription>View and manage sales receipts.</CardDescription>
        </CardHeader>
      </Card>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <div className='flex flex-col gap-4'>
            <div className='flex flex-wrap items-center gap-3'>
              <div className='relative flex-1 min-w-[200px]'>
                <Search className='absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground' />
                <Input
                  placeholder='Search receipt number...'
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className='pl-9'
                />
              </div>
              <Select value={customerId} onValueChange={setCustomerId}>
                <SelectTrigger className='w-[160px]'>
                  <SelectValue placeholder='Customer' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value=''>All Customers</SelectItem>
                  {customers.map((c: any) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.user?.firstname ?? c.company_name ?? `Customer #${c.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select value={paymentMethod} onValueChange={setPaymentMethod}>
                <SelectTrigger className='w-[150px]'>
                  <SelectValue placeholder='Payment' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value=''>All Methods</SelectItem>
                  <SelectItem value='cash'>Cash</SelectItem>
                  <SelectItem value='credit_card'>Credit Card</SelectItem>
                  <SelectItem value='mobile_money'>Mobile Money</SelectItem>
                  <SelectItem value='bank'>Bank</SelectItem>
                </SelectContent>
              </Select>
              <Select value={status} onValueChange={setStatus}>
                <SelectTrigger className='w-[130px]'>
                  <SelectValue placeholder='Status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value=''>All Status</SelectItem>
                  <SelectItem value='completed'>Completed</SelectItem>
                  <SelectItem value='refunded'>Refunded</SelectItem>
                  <SelectItem value='voided'>Voided</SelectItem>
                </SelectContent>
              </Select>
              <Input
                type='date'
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className='w-[140px]'
                placeholder='From'
              />
              <Input
                type='date'
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className='w-[140px]'
                placeholder='To'
              />
              <Button onClick={handleFilter} size='sm' className='gap-2'>
                <Filter className='h-4 w-4' />
                Filter
              </Button>
              <Button onClick={handleClear} variant='outline' size='sm'>
                Clear
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Receipt #</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Cashier</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Payment</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {receipts.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={8} className='text-center text-muted-foreground py-8'>
                    No receipts found
                  </TableCell>
                </TableRow>
              ) : (
                receipts.map((receipt: any) => (
                  <TableRow key={receipt.id}>
                    <TableCell className='font-medium'>{receipt.receipt_number}</TableCell>
                    <TableCell>
                      {receipt.customer
                        ? (receipt.customer.user?.firstname ?? receipt.customer.company_name ?? `Customer #${receipt.customer.id}`)
                        : '-'}
                    </TableCell>
                    <TableCell>
                      {receipt.user ? `${receipt.user.firstname ?? ''} ${receipt.user.lastname ?? ''}` : '-'}
                    </TableCell>
                    <TableCell>{currency} {Number(receipt.total).toLocaleString()}</TableCell>
                    <TableCell className='capitalize'>{receipt.payment_method?.replace(/_/g, ' ')}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant(receipt.status) as any}>{receipt.status}</Badge>
                    </TableCell>
                    <TableCell>{format(new Date(receipt.created_at), 'MMM d, yyyy')}</TableCell>
                    <TableCell>
                      <div className='flex items-center gap-2'>
                        <Link
                          to={`${receipt.id}`}
                          className='flex items-center gap-1 text-sm text-blue-400 hover:underline'
                        >
                          <Eye className='h-3.5 w-3.5' />
                          View
                        </Link>
                        <a
                          href={`${baseUrl}/receipts/${receipt.id}/pdf?token=${localStorage.getItem('token')}`}
                          target='_blank'
                          rel='noopener noreferrer'
                          className='flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground'
                        >
                          <Download className='h-3.5 w-3.5' />
                          PDF
                        </a>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>

          {totalPages > 1 && (
            <div className='mt-4'>
              <PaginationComponent
                currentPage={currentPage}
                totalPages={totalPages}
                onPageChange={setCurrentPage}
              />
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
