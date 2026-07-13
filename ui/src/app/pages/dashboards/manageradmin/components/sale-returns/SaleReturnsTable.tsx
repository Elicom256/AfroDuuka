import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';

interface SaleReturnsTableProps {
  saleReturns: any[];
}

export const SaleReturnsTable = ({ saleReturns }: SaleReturnsTableProps) => {
  const { currency } = useCurrency();
  const navigate = useNavigate();
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const totalPages = Math.ceil((saleReturns?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginated = saleReturns?.slice(startIndex, startIndex + itemsPerPage);

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            <TableHead>Reason</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Restock</TableHead>
            <TableHead>Refund ({currency})</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginated?.length > 0 ? (
            paginated.map((sr: any, i: number) => (
              <TableRow
                key={sr.id}
                onClick={() => navigate(`${sr.id}`, { relative: 'route' })}
                className='cursor-pointer'
              >
                <TableCell>{startIndex + i + 1}</TableCell>
                <TableCell>{sr.reason?.slice(0, 30) || '-'}</TableCell>
                <TableCell>{format(new Date(sr.created_at), 'PPP')}</TableCell>
                <TableCell>{sr.restock ? 'Yes' : 'No'}</TableCell>
                <TableCell>{Number(sr.refund_amount).toLocaleString()}</TableCell>
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell colSpan={5}>
                <p className='text-center py-3 text-muted-foreground'>No sale returns recorded yet.</p>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
