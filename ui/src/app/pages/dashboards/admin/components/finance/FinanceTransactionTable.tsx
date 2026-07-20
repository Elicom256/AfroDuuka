import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { Eye } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';

type FinanceTransactionRecord = {
  id: number;
  transaction_code: string;
  type: string;
  amount: number;
  currency: string;
  category: string;
  description: string;
  status: string;
  running_balance?: number;
  transaction_date: string;
  branch?: { name: string };
  created_by?: { name: string };
};

type FinanceTransactionTableProps = {
  records: FinanceTransactionRecord[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
};

const typeVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  sale: 'default',
  purchase: 'destructive',
  expense: 'destructive',
  refund: 'secondary',
  payment_in: 'default',
  payment_out: 'secondary',
  adjustment: 'outline',
};

const typeColors: Record<string, string> = {
  sale: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  purchase: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  expense: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  refund: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
  payment_in: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  payment_out: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
  adjustment: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
};

const isOutflow = (type: string) =>
  ['purchase', 'expense', 'payment_out', 'refund'].includes(type);

export const FinanceTransactionTable = ({
  records,
  currentPage,
  totalPages,
  onPageChange,
}: FinanceTransactionTableProps) => {
  const { currency } = useCurrency();

  return (
    <Card>
      <CardHeader>
        <CardTitle>Transactions</CardTitle>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Code</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead className='text-right'>Amount</TableHead>
              <TableHead className='text-right'>Running Balance</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Date</TableHead>
              <TableHead className='w-20'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {records.length === 0 ? (
              <TableRow>
                <TableCell colSpan={10} className='py-10 text-center text-muted-foreground'>
                  No transactions found.
                </TableCell>
              </TableRow>
            ) : (
              records.map((record) => {
                const outflow = isOutflow(record.type);
                return (
                  <TableRow key={record.id}>
                    <TableCell className='font-mono text-xs'>{record.transaction_code}</TableCell>
                    <TableCell>
                      <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${typeColors[record.type] ?? 'bg-gray-100 text-gray-800'}`}
                      >
                        {record.type.replace('_', ' ')}
                      </span>
                    </TableCell>
                    <TableCell className='max-w-xs truncate'>{record.description || '—'}</TableCell>
                    <TableCell>
                      <Badge variant='outline'>{record.category || '—'}</Badge>
                    </TableCell>
                    <TableCell>{record.branch?.name ?? '—'}</TableCell>
                    <TableCell
                      className={`text-right font-medium ${outflow ? 'text-red-600' : 'text-green-600'}`}
                    >
                      {outflow ? '-' : '+'}
                      {currency} {Number(record.amount).toLocaleString()}
                    </TableCell>
                    <TableCell className='text-right font-medium'>
                      {record.running_balance != null
                        ? `${currency} ${Number(record.running_balance).toLocaleString()}`
                        : '—'}
                    </TableCell>
                    <TableCell>
                      <Badge variant={typeVariant[record.type] ?? 'outline'}>
                        {record.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {record.transaction_date
                        ? format(new Date(record.transaction_date), 'dd MMM yyyy')
                        : '—'}
                    </TableCell>
                    <TableCell>
                      <Button variant='ghost' size='icon' asChild>
                        <Link to={`/admin/finance/transactions/${record.id}`}>
                          <Eye className='h-4 w-4' />
                        </Link>
                      </Button>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
        {totalPages > 1 && (
          <div className='py-4'>
            <PaginationComponent
              currentPage={currentPage}
              totalPages={totalPages}
              onPageChange={onPageChange}
            />
          </div>
        )}
      </CardContent>
    </Card>
  );
};
