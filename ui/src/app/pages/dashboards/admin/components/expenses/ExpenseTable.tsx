import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { CheckCircle, XCircle, BarChart3 } from 'lucide-react';
import { useCurrency } from '@/app/hooks/useCurrency';

type ExpenseTableProps = {
  expenses: any[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  onApprove: (item: any) => void;
  onEdit: (item: any) => void;
  onDelete: (id: number) => void;
};

const statusVariant = (status: string) => {
  if (status === 'approved') return 'default';
  if (status === 'cancelled') return 'destructive';
  return 'secondary';
};

export const ExpenseTable = ({ expenses, currentPage, totalPages, onPageChange, onApprove, onEdit, onDelete }: ExpenseTableProps) => {
  const { currency } = useCurrency();

  return (
    <Card>
      <CardHeader>
        <CardTitle>Expense Records</CardTitle>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Category</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Vendor</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead className='text-right'>Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Date</TableHead>
              <TableHead className='w-32'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {expenses.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className='py-10 text-center text-muted-foreground'>
                  No expenses found.
                </TableCell>
              </TableRow>
            ) : (
              expenses.map((exp: any) => (
                <TableRow key={exp.id}>
                  <TableCell>
                    <Badge variant='outline'>{exp.category?.name ?? '—'}</Badge>
                  </TableCell>
                  <TableCell>{exp.description || '—'}</TableCell>
                  <TableCell>{exp.vendor || '—'}</TableCell>
                  <TableCell>{exp.business_branch?.name || '—'}</TableCell>
                  <TableCell className='text-right font-medium text-red-600'>
                    {currency} {Number(exp.amount).toLocaleString()}
                  </TableCell>
                  <TableCell>
                    <Badge variant={statusVariant(exp.status)}>{exp.status}</Badge>
                  </TableCell>
                  <TableCell>{exp.payment_date ? format(new Date(exp.payment_date), 'dd MMM yyyy') : '—'}</TableCell>
                  <TableCell>
                    <div className='flex gap-1'>
                      {exp.status === 'pending' && (
                        <Button variant='ghost' size='icon' onClick={() => onApprove(exp)} title='Approve'>
                          <CheckCircle className='h-4 w-4 text-green-500' />
                        </Button>
                      )}
                      <Button variant='ghost' size='icon' onClick={() => onEdit(exp)} title='Edit'>
                        <BarChart3 className='h-4 w-4' />
                      </Button>
                      <Button variant='ghost' size='icon' onClick={() => onDelete(exp.id)} title='Delete'>
                        <XCircle className='h-4 w-4 text-red-500' />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
        {totalPages > 1 && (
          <div className='flex items-center justify-between p-4'>
            <p className='text-sm text-muted-foreground'>
              Page {currentPage} of {totalPages}
            </p>
            <div className='flex gap-2'>
              <Button variant='outline' size='sm' disabled={currentPage <= 1} onClick={() => onPageChange(Math.max(1, currentPage - 1))}>
                Previous
              </Button>
              <Button variant='outline' size='sm' disabled={currentPage >= totalPages} onClick={() => onPageChange(currentPage + 1)}>
                Next
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
