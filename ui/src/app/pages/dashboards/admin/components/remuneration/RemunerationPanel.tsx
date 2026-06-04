import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';

type RemunerationPanelProps = {
  payroll: any[];
  totalPaid: number;
  employeeCount: number;
  pending: number;
};

export const RemunerationPanel = ({ payroll, totalPaid, employeeCount, pending }: RemunerationPanelProps) => {
  const formatted = (payment_date: any) => format(new Date(payment_date), 'dd MMM yyyy');
  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total paid</CardTitle>
            <CardDescription>Employee disbursements over the active period.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{Number(totalPaid).toLocaleString()}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Employees paid</CardTitle>
            <CardDescription>Unique payroll recipients.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{employeeCount}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Pending payouts</CardTitle>
            <CardDescription>Remuneration items still awaiting approval.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{pending}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader>
          <CardTitle>Employee remittance</CardTitle>
          <CardDescription>Payroll rows for the latest payout cycle.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee</TableHead>
                <TableHead>Branch</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Payroll Period</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {payroll.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className='text-center py-10 text-muted-foreground'>
                    No payroll records found.
                  </TableCell>
                </TableRow>
              ) : (
                payroll.slice(0, 12).map((item, index) => {
                  return (
                    <TableRow key={item.id ?? index}>
                      <TableCell>
                        {`${item.worker?.user?.firstname || ''} ${item.worker?.user?.lastname || ''}`.trim() ||
                          item.worker?.user?.email ||
                          '—'}
                      </TableCell>
                      <TableCell>{item.worker?.user?.business_branch?.name ?? '—'}</TableCell>
                      <TableCell>{Number(item.amount).toLocaleString()}</TableCell>
                      <TableCell>{formatted(item.payment_date)}</TableCell>
                      <TableCell>
                        <Badge variant='secondary'>{item?.status}</Badge>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
};
