import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SalaryForm } from './SalaryForm';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

type SalaryPanelProps = {
  salaries: any[];
  monthlyPayroll: number;
  activeCount: number;
  onEditRow?: (item: any) => void;
  onDeleteRow?: (id: number) => void;
};

export const SalaryPanel = ({ salaries, monthlyPayroll, activeCount, onEditRow, onDeleteRow }: SalaryPanelProps) => {
  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-2'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Monthly Payroll</CardTitle>
            <CardDescription>Sum of all active monthly salaries.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{Number(monthlyPayroll).toLocaleString()}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Active Salaries</CardTitle>
            <CardDescription>Roles currently carrying a salary.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{activeCount}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader className='flex items-center justify-between'>
          <div>
            <CardTitle>Salaries</CardTitle>
            <CardDescription>Each role carries one amount, paid to everyone holding it.</CardDescription>
          </div>
          <SalaryForm />
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Role</TableHead>
                <TableHead>Branch</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Period</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Set By</TableHead>
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
                    <TableCell>{item.role?.name ?? '—'}</TableCell>
                    <TableCell>{item.business_branch?.name ?? 'All branches'}</TableCell>
                    <TableCell>{Number(item.amount).toLocaleString()}</TableCell>
                    <TableCell className='capitalize'>{item.period ?? '—'}</TableCell>
                    <TableCell>
                      <Badge variant={item.status === 'active' ? 'default' : 'secondary'}>
                        {item.status ?? 'Unknown'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {item.set_by?.name ||
                        `${item.set_by?.firstname ?? ''} ${item.set_by?.lastname ?? ''}`.trim() ||
                        '—'}
                    </TableCell>
                    <TableCell className='text-right'>
                      <div className='flex justify-end gap-2'>
                        <Button variant='outline' size='sm' onClick={() => onEditRow?.(item)}>
                          Edit
                        </Button>
                        <ConfirmDeleteButton
                          onConfirm={() => onDeleteRow?.(item.id)}
                          title='Delete this salary?'
                          description='The salary record is removed. This cannot be undone.'
                          trigger={
                            <Button variant='destructive' size='sm'>
                              Delete
                            </Button>
                          }
                        />
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
