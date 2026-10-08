import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { Eye } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';
import {
  amountSign,
  amountTone,
  isOutflow,
  type FinanceTransactionRecord,
  typeVariant,
} from './financeTransaction';
import { TransactionDirectionControl, TransactionTypeBadge } from './financeTransactionComponents';

type FinanceTransactionTableProps = {
  records: FinanceTransactionRecord[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
};

export const FinanceTransactionTable = ({
  records,
  currentPage,
  totalPages,
  onPageChange,
}: FinanceTransactionTableProps) => {
  const { currency } = useCurrency();

  const columnCount = 11;

  return (
    <Card>
      <CardHeader>
        <CardTitle>Transactions</CardTitle>
      </CardHeader>
      <CardContent className='p-0'>
        <div className='overflow-x-auto'>
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
                <TableHead className='w-24'>Direction</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {records.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={columnCount} className='py-10 text-center text-muted-foreground'>
                    No transactions found.
                  </TableCell>
                </TableRow>
              ) : (
                records.map((record) => {
                  const outflow = isOutflow(record);

                  return (
                    <TableRow key={record.id}>
                      <TableCell className='font-mono text-xs'>{record.transaction_code}</TableCell>
                      <TableCell>
                        <TransactionTypeBadge type={record.type} />
                      </TableCell>
                      <TableCell className='max-w-xs truncate'>{record.description || '—'}</TableCell>
                      <TableCell>
                        <Badge variant='outline'>{record.category || '—'}</Badge>
                      </TableCell>
                      <TableCell>{record.branch?.name ?? '—'}</TableCell>
                      <TableCell
                        className={`text-right font-medium ${amountTone(outflow)}`}
                      >
                        {amountSign(outflow)}
                        {currency} {Number(record.amount).toLocaleString()}
                      </TableCell>
                      <TableCell className='text-right font-medium'>
                        {record.running_balance != null
                          ? `${currency} ${Number(record.running_balance).toLocaleString()}`
                          : '—'}
                      </TableCell>
                      <TableCell>
                        <Badge variant={typeVariant[record.type] ?? 'outline'}>{record.status}</Badge>
                      </TableCell>
                      <TableCell>
                        {record.transaction_date
                          ? format(new Date(record.transaction_date), 'dd MMM yyyy')
                          : '—'}
                      </TableCell>
                      <TableCell>
                        <Button variant='ghost' size='icon' asChild>
                          <Link to={`/dashboard/finance/transactions/${record.id}`}>
                            <Eye className='h-4 w-4' />
                          </Link>
                        </Button>
                      </TableCell>
                      <TableCell>
                        {record.type === 'adjustment' && !record.direction ? (
                          <TransactionDirectionControl record={record} />
                        ) : record.direction ? (
                          <Badge variant='outline' className='whitespace-nowrap'>
                            {record.direction === 'credit' ? 'Money in' : 'Money out'}
                          </Badge>
                        ) : (
                          '—'
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </div>
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
