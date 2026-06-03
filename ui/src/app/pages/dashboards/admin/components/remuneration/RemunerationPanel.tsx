import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type RemunerationPanelProps = {
  payroll: any[];
};

const formatCurrency = (value: unknown) => {
  const amount = Number(value ?? 0);
  if (Number.isNaN(amount)) return 'UGX 0';
  return `UGX ${amount.toLocaleString()}`;
};

const formatDate = (value: unknown) => {
  if (!value) return '—';
  const date = new Date(value as string);
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('en-US');
};

export const RemunerationPanel = ({ payroll }: RemunerationPanelProps) => {
  const records = Array.isArray(payroll) ? payroll : [];
  const totalPaid = records.reduce((sum, item) => sum + Number(item.amount ?? item.paid_amount ?? 0), 0);
  const paidCount = records.filter(
    (item) => String(item.status || item.payment_status || '').toLowerCase() === 'paid',
  ).length;
  const pendingCount = records.filter(
    (item) => String(item.status || item.payment_status || '').toLowerCase() === 'pending',
  ).length;
  const employeeCount = new Set(records.map((item) => item.employee?.id ?? item.user?.id ?? item.employee_id)).size;

  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total paid</CardTitle>
            <CardDescription>Employee disbursements over the active period.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{formatCurrency(totalPaid)}</p>
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
            <p className='text-3xl font-semibold'>{pendingCount}</p>
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
              {records.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className='text-center py-10 text-muted-foreground'>
                    No payroll records found.
                  </TableCell>
                </TableRow>
              ) : (
                records.slice(0, 12).map((item, index) => {
                  const status = String(item.status || item.payment_status || 'unknown').toLowerCase();
                  const variant = status === 'paid' ? 'default' : status === 'pending' ? 'secondary' : 'outline';
                  return (
                    <TableRow key={item.id ?? index}>
                      <TableCell>
                        {item.employee?.name ||
                          `${item.user?.firstname || ''} ${item.user?.lastname || ''}`.trim() ||
                          item.employee_name ||
                          '—'}
                      </TableCell>
                      <TableCell>
                        {item.employee?.branch?.name || item.branch?.name || item.business_branch?.name || '—'}
                      </TableCell>
                      <TableCell>{formatCurrency(item.amount ?? item.paid_amount)}</TableCell>
                      <TableCell>{formatDate(item.pay_period || item.payout_period || item.period)}</TableCell>
                      <TableCell>
                        <Badge variant={variant as any}>{String(status).toUpperCase()}</Badge>
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
