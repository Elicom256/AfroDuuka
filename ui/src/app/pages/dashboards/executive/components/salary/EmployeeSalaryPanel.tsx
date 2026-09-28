import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { EmployeeSalaryForm } from './EmployeeSalaryForm';
import { useCurrency } from '@/app/hooks/useCurrency';

type EmployeeSalaryPanelProps = {
  salaries: any[];
  totalMonthly: number;
  activeCount: number;
  onEditRow?: (item: any) => void;
  onDeleteRow?: (id: number) => void;
};

export const EmployeeSalaryPanel = ({
  salaries,
  totalMonthly,
  activeCount,
  onEditRow,
  onDeleteRow,
}: EmployeeSalaryPanelProps) => {
  const { currency } = useCurrency();
  const formatted = (date: any) => format(new Date(date), 'dd MMM yyyy');

  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total Monthly Payroll</CardTitle>
            <CardDescription>Sum of all active employee salaries.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{Number(totalMonthly).toLocaleString()}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Active Salaries</CardTitle>
            <CardDescription>Employees with an active salary record.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{activeCount}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader className='flex items-center justify-between'>
          <div>
            <CardTitle>Employee Salaries</CardTitle>
            <CardDescription>Monthly salary records for all employees.</CardDescription>
          </div>
          <EmployeeSalaryForm />
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Currency</TableHead>
                <TableHead>Effective Date</TableHead>
                <TableHead>End Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className='text-right'>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {salaries.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} className='text-center py-10 text-muted-foreground'>
                    No salary records found.
                  </TableCell>
                </TableRow>
              ) : (
                salaries.map((item, index) => (
                  <TableRow key={item.id ?? index}>
                    <TableCell>
                      {`${item.worker?.user?.firstname || ''} ${item.worker?.user?.lastname || ''}`.trim() ||
                        item.worker?.user?.email ||
                        '—'}
                    </TableCell>
                    <TableCell>{Number(item.amount).toLocaleString()}</TableCell>
                    <TableCell>{item.currency || currency}</TableCell>
                    <TableCell>{formatted(item.effective_date)}</TableCell>
                    <TableCell>{item.end_date ? formatted(item.end_date) : '—'}</TableCell>
                    <TableCell>
                      <Badge variant={item.status === 'active' ? 'default' : 'secondary'}>
                        {item.status ?? 'Unknown'}
                      </Badge>
                    </TableCell>
                    <TableCell className='text-right'>
                      <div className='flex justify-end gap-2'>
                        <Button variant='outline' size='sm' onClick={() => onEditRow?.(item)}>
                          Edit
                        </Button>
                        <Button variant='destructive' size='sm' onClick={() => onDeleteRow?.(item.id)}>
                          Delete
                        </Button>
                      </div>
                    </TableCell>
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
