import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';

interface PurchaseReturnsTableProps {
  purchaseReturns: any[];
}

export const PurchaseReturnsTable = ({ purchaseReturns }: PurchaseReturnsTableProps) => {
  const { currency } = useCurrency();
  const navigate = useNavigate();
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const totalPages = Math.ceil((purchaseReturns?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginated = purchaseReturns?.slice(startIndex, startIndex + itemsPerPage);

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            <TableHead>Purchase ID</TableHead>
            <TableHead>Reason</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Restock</TableHead>
            <TableHead>Refund ({currency})</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginated?.length > 0 ? (
            paginated.map((pr: any, i: number) => (
              <TableRow
                key={pr.id}
                onClick={() => navigate(`/manager/purchase-returns/${pr.id}`)}
                className='cursor-pointer'
              >
                <TableCell>{startIndex + i + 1}</TableCell>
                <TableCell>#{pr.purchase_id}</TableCell>
                <TableCell>{pr.reason?.slice(0, 30) || '-'}</TableCell>
                <TableCell>{format(new Date(pr.created_at), 'PPP')}</TableCell>
                <TableCell>{pr.restock ? 'Yes' : 'No'}</TableCell>
                <TableCell>{Number(pr.refund_amount).toLocaleString()}</TableCell>
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell colSpan={6}>
                <p className='text-center py-3 text-muted-foreground'>No purchase returns recorded yet.</p>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
