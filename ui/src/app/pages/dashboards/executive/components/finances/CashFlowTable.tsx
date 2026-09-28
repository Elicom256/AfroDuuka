import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';

type CashFlowRecord = {
  id: number;
  transaction_code: string;
  type: string;
  amount: number;
  currency: string;
  category: string;
  description: string;
  status: string;
  transaction_date: string;
  branch?: { name: string };
  created_by?: { name: string };
};

type CashFlowTableProps = {
  records: CashFlowRecord[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
};

const typeVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  sale: 'default',
  payment_in: 'default',
  refund: 'secondary',
  purchase: 'destructive',
  expense: 'destructive',
  payment_out: 'destructive',
};

export const CashFlowTable = ({ records, currentPage, totalPages, onPageChange }: CashFlowTableProps) => {
  const { currency } = useCurrency();

  return (
    <Card>
      <CardHeader>
        <CardTitle>Cash Flow Records</CardTitle>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Code</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead className='text-right'>Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Date</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {records.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className='py-10 text-center text-muted-foreground'>
                  No cash flow records found.
                </TableCell>
              </TableRow>
            ) : (
              records.map((record) => (
                <TableRow key={record.id}>
                  <TableCell className='font-mono text-xs'>{record.transaction_code}</TableCell>
                  <TableCell>{record.description}</TableCell>
                  <TableCell>
                    <Badge variant='outline'>{record.category}</Badge>
                  </TableCell>
                  <TableCell>{record.branch?.name ?? '—'}</TableCell>
                  <TableCell className={`text-right font-medium ${record.type === 'purchase' || record.type === 'expense' || record.type === 'payment_out' ? 'text-red-600' : 'text-green-600'}`}>
                    {record.type === 'purchase' || record.type === 'expense' || record.type === 'payment_out' ? '-' : '+'}
                    {currency} {Number(record.amount).toLocaleString()}
                  </TableCell>
                  <TableCell>
                    <Badge variant={typeVariant[record.type] ?? 'outline'}>{record.status}</Badge>
                  </TableCell>
                  <TableCell>{record.transaction_date ? format(new Date(record.transaction_date), 'dd MMM yyyy') : '—'}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
        {totalPages > 1 && (
          <div className='py-4'>
            <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={onPageChange} />
          </div>
        )}
      </CardContent>
    </Card>
  );
};
