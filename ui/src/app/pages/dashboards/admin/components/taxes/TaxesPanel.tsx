import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type TaxesPanelProps = {
  taxes: any[];
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

export const TaxesPanel = ({ taxes }: TaxesPanelProps) => {
  const records = Array.isArray(taxes) ? taxes : [];
  const totalTax = records.reduce((sum, record) => sum + Number(record.amount ?? record.tax_amount ?? 0), 0);
  const outstanding = records.reduce(
    (sum, record) =>
      sum +
      (String(record.status || record.payment_status || '').toLowerCase() === 'due'
        ? Number(record.amount ?? record.tax_amount ?? 0)
        : 0),
    0,
  );
  const paidCount = records.filter(
    (record) => String(record.status || record.payment_status || '').toLowerCase() === 'paid',
  ).length;
  const dueCount = records.filter(
    (record) => String(record.status || record.payment_status || '').toLowerCase() === 'due',
  ).length;

  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total tax liability</CardTitle>
            <CardDescription>All tax entries for the current business.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{formatCurrency(totalTax)}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Outstanding tax</CardTitle>
            <CardDescription>Open tax balances waiting to be paid.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{formatCurrency(outstanding)}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Open filings</CardTitle>
            <CardDescription>Pending and paid tax items.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{paidCount + dueCount}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader>
          <CardTitle>Tax breakdown</CardTitle>
          <CardDescription>Recent tax filings and remit history.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Business</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Due date</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {records.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className='text-center py-10 text-muted-foreground'>
                    No tax records found.
                  </TableCell>
                </TableRow>
              ) : (
                records.slice(0, 12).map((record, index) => {
                  const status = String(record.status || record.payment_status || 'unknown').toLowerCase();
                  const variant = status === 'paid' ? 'default' : status === 'due' ? 'secondary' : 'outline';
                  return (
                    <TableRow key={record.id ?? index}>
                      <TableCell>{record.business?.name || record.company_name || '—'}</TableCell>
                      <TableCell>{record.tax_type || record.type || 'General'}</TableCell>
                      <TableCell>{formatCurrency(record.amount ?? record.tax_amount)}</TableCell>
                      <TableCell>{formatDate(record.due_date || record.due_at || record.deadline)}</TableCell>
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
