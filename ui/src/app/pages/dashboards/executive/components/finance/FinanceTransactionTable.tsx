import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { ArrowDownLeft, ArrowUpRight, Eye } from 'lucide-react';
import { format } from 'date-fns';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';
import {
  type CashFlowDirection,
  useSetCashFlowDirectionMutation,
} from '@/app/store/features/business/executive/cashFlowQuery';

type FinanceTransactionRecord = {
  id: number;
  transaction_code: string;
  type: string;
  /**
   * Present only on an adjustment. Null means the row was written before a direction was
   * required, so the server cannot say which way the money moved and excludes it from the
   * cash balance — the reason the executive dashboard warns about these.
   */
  direction?: CashFlowDirection | null;
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

/**
 * Does this row move money out of the business?
 *
 * Keyed off `type` alone, an adjustment was always drawn as an inflow — a green `+` on an
 * amount that could equally have left the till. `direction` is what actually decides, and
 * it is only present on an adjustment. When it is null the row is one of the legacy
 * adjustments the server cannot sign, so it is drawn as neither: colouring it either way
 * would be a guess, and the row carries a control to record the answer instead.
 */
const isOutflow = (record: Pick<FinanceTransactionRecord, 'type' | 'direction'>) => {
  if (record.type === 'adjustment') {
    return record.direction === 'debit' ? true : record.direction === 'credit' ? false : null;
  }

  return ['purchase', 'expense', 'payment_out', 'refund'].includes(record.type);
};

const DIRECTION_OPTIONS: { value: CashFlowDirection; label: string; Icon: typeof ArrowDownLeft }[] = [
  { value: 'credit', label: 'Money In', Icon: ArrowDownLeft },
  { value: 'debit', label: 'Money Out', Icon: ArrowUpRight },
];

/**
 * Record a direction on an adjustment that has none.
 *
 * Only rendered for an unsigned adjustment. The server refuses to clear a direction that is
 * already recorded, so this is a repair control rather than an edit one.
 */
const DirectionControl = ({ record }: { record: FinanceTransactionRecord }) => {
  const [setDirection, { isLoading }] = useSetCashFlowDirectionMutation();

  return (
    <div className='flex items-center gap-1'>
      {DIRECTION_OPTIONS.map(({ value, label, Icon }) => (
        <Button
          key={value}
          variant='ghost'
          size='icon'
          disabled={isLoading}
          title={`Mark as ${label.toLowerCase()}`}
          aria-label={`Mark ${record.transaction_code} as ${label.toLowerCase()}`}
          onClick={async () => {
            try {
              const res = await setDirection({ id: record.id, direction: value }).unwrap();
              toast.success(res?.message || `Marked as ${label.toLowerCase()}`);
            } catch {
              toast.error('Could not record the direction');
            }
          }}
        >
          <Icon className='h-4 w-4' />
        </Button>
      ))}
    </div>
  );
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
                        <span
                          className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${typeColors[record.type] ?? 'bg-gray-100 text-muted-foreground'}`}
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
                        className={`text-right font-medium ${outflow === null ? 'text-muted-foreground' : outflow ? 'text-red-600' : 'text-green-600'}`}
                      >
                        {outflow === null ? '' : outflow ? '-' : '+'}
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
                          <DirectionControl record={record} />
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
