import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';
import { Link } from 'react-router-dom';
import { useState } from 'react';

type TaxRecord = {
  id?: string | number;
  amount?: number;
  paid_amount?: number;
  balance?: number;
  branch?: {
    name?: any;
  };
  due_date?: string;
  payment_date?: string;
  status?: string;
};

type TaxesPanelProps = {
  taxes: TaxRecord[];
  headers: any[];
  handleUpdateTaxPaymentStatus?: (id: string | number, status: string) => void;
  onDeleteTaxPayment?: (id: string) => void;
};

export const TaxPaymentsPanel = ({
  taxes,
  headers,
  handleUpdateTaxPaymentStatus,
  onDeleteTaxPayment,
}: TaxesPanelProps) => {
  const [selectedStatuses, setSelectedStatuses] = useState<Record<string, string>>({});

  const dateFormat = (date: any) => {
    return date ? format(new Date(date), 'dd MMM yyyy') : '—';
  };

  const handleStatusChange = async (id: string | number, value: string) => {
    setSelectedStatuses((prev) => ({ ...prev, [String(id)]: value }));
    handleUpdateTaxPaymentStatus?.(id, value);
  };
  const statuses = ['unpaid', 'partial', 'paid', 'overdue', 'waived', 'refunded'];
  return (
    <div className='space-y-6'>
      <Card className='overflow-hidden rounded-3xl border border-border/70 bg-card'>
        <CardHeader>
          <CardTitle>Tax breakdown</CardTitle>
          <CardDescription>Recent tax filings and payment history.</CardDescription>
        </CardHeader>

        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                {headers.map((header) => (
                  <TableHead key={header}>{header}</TableHead>
                ))}
                <TableHead>Action</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {taxes.length <= 0 ? (
                <TableRow>
                  <TableCell colSpan={8} className='py-10 text-center text-muted-foreground'>
                    No tax records found.
                  </TableCell>
                </TableRow>
              ) : (
                taxes?.map((tax: any) => {
                  const currentStatus = selectedStatuses[String(tax.id)] ?? tax.status ?? 'pending';

                  return (
                    <TableRow key={tax.id}>
                      <TableCell>{tax.amount?.toLocaleString() ?? '—'}</TableCell>
                      <TableCell>{tax.paid_amount?.toLocaleString() ?? '—'}</TableCell>
                      <TableCell>{tax.balance?.toLocaleString() ?? '—'}</TableCell>
                      <TableCell>{tax?.business_branch?.name ?? '—'}</TableCell>
                      <TableCell>{dateFormat(tax.due_date)}</TableCell>
                      <TableCell>{dateFormat(tax.payment_date)}</TableCell>
                      <TableCell>
                        <Badge variant={tax.balance ? 'secondary' : 'default'}>{tax.status}</Badge>
                      </TableCell>
                      <TableCell className='space-y-2'>
                        <Select
                          value={currentStatus}
                          onValueChange={(value) => handleStatusChange(tax.id ?? '', value)}
                        >
                          <SelectTrigger className='w-full'>
                            <SelectValue placeholder='Status' />
                          </SelectTrigger>
                          <SelectContent>
                            {statuses?.map((status, i) => (
                              <SelectItem key={i} value={status}>
                                {status}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <Button
                          variant='destructive'
                          size='sm'
                          className='w-full'
                          onClick={() => onDeleteTaxPayment?.(tax.id)}
                        >
                          Delete
                        </Button>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
        <CardFooter>
          <Link to='/admin/obligated-taxes'>Taxes Obligated to</Link>
        </CardFooter>
      </Card>
    </div>
  );
};
