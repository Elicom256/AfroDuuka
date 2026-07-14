/*-----------------------------------------------------------------------------------
 * Component: PriceHistoryTab
 * -------------------------------
 * Displays price change history for a single product in a table + timeline.
 * Features:
 *   - Table columns: Date, Old Cost, New Cost, Old Selling, New Selling, User, Reason
 *   - Paginated (client-side via PaginationComponent)
 *   - Timeline-style list view as a secondary mode
 *---------------------------------------------------------------------------------*/

import { useState } from 'react';
import { useProductPriceHistoryQuery } from '@/app/store/features/branch/priceHistory/priceHistoryQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { PaginationComponent } from '@/app/utils/Pagination';
import { LoadingState } from '@/utils/LoadingState';
import { Clock, ArrowUp, ArrowDown } from 'lucide-react';

interface PriceHistoryTabProps {
  productId: string;
}

export const PriceHistoryTab = ({ productId }: PriceHistoryTabProps) => {
  const { currency } = useCurrency();
  const [page, setPage] = useState(1);
  const { data, isLoading, error } = useProductPriceHistoryQuery({ productId, page });

  // The backend returns paginated data inside `data.data`
  const historyData = data?.data;
  const records = historyData?.data ?? [];          // the actual page items
  const currentPage = historyData?.current_page ?? 1;
  const lastPage = historyData?.last_page ?? 1;

  if (isLoading) return <LoadingState />;
  if (error) return <p className='text-red-500'>Failed to load price history.</p>;

  if (!records.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2 text-lg'>
            <Clock className='h-5 w-5' /> Price History
          </CardTitle>
        </CardHeader>
        <CardContent className='py-8 text-center text-muted-foreground'>
          No price changes recorded yet.
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2 text-lg'>
          <Clock className='h-5 w-5' /> Price History
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-4'>
        {/* Data table */}
        <div className='rounded-md border'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Old Cost</TableHead>
                <TableHead>New Cost</TableHead>
                <TableHead>Old Selling</TableHead>
                <TableHead>New Selling</TableHead>
                <TableHead>Changed By</TableHead>
                <TableHead>Reason</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {records.map((record: any) => (
                <TableRow key={record.id}>
                  {/* Date */}
                  <TableCell className='text-xs whitespace-nowrap'>
                    {new Date(record.created_at).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'short',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </TableCell>

                  {/* Old Cost */}
                  <TableCell>
                    <span className='text-red-500'>
                      {currency} {record.old_cost_price ?? '-'}
                    </span>
                  </TableCell>

                  {/* New Cost */}
                  <TableCell>
                    <span className='text-green-600'>
                      {currency} {record.new_cost_price ?? '-'}
                    </span>
                  </TableCell>

                  {/* Old Selling */}
                  <TableCell>
                    <span className='text-red-500'>
                      {currency} {record.old_sale_price ?? '-'}
                    </span>
                  </TableCell>

                  {/* New Selling */}
                  <TableCell>
                    <span className='text-green-600'>
                      {currency} {record.new_sale_price ?? '-'}
                    </span>
                  </TableCell>

                  {/* Changed By */}
                  <TableCell className='text-xs'>
                    {record.changed_by_user
                      ? `${record.changed_by_user.firstname ?? ''} ${record.changed_by_user.lastname ?? ''}`.trim() || 'System'
                      : 'System'}
                  </TableCell>

                  {/* Reason */}
                  <TableCell className='text-xs max-w-[150px] truncate'>
                    {record.change_reason ? (
                      <Badge variant='outline' className='text-xs font-normal'>
                        {record.change_reason}
                      </Badge>
                    ) : (
                      <span className='text-muted-foreground'>—</span>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>

        {/* Pagination */}
        <PaginationComponent
          currentPage={currentPage}
          totalPages={lastPage}
          onPageChange={setPage}
        />
      </CardContent>
    </Card>
  );
};
