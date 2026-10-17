import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useNavigate } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';
import { formatDate } from '@/app/utils/safeDate';
import { useRolePermissions } from '@/lib/useRolePermissions';
import { RowSyncStatus } from '@/components/offline/RowSyncStatus';

interface SalesTableProps {
  sales: any[];
  products: any[];
}

export const SalesTable = ({ sales }: SalesTableProps) => { 
  const { currency } = useCurrency();
  const navigate = useNavigate();
  const [currentPage, setCurrentPage] = useState(1);
  useRolePermissions();
  const itemsPerPage = 10;

  const totalPages = Math.ceil((sales?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedSales = sales?.slice(startIndex, startIndex + itemsPerPage);

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Note</TableHead>
            <TableHead> Amount({currency})</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedSales?.map((sale, i) => {
            const totalPrice = Number(sale.total_amount ?? sale.total_amount ?? 0);
            return (
              <TableRow key={sale.id} onClick={() => navigate(`/dashboard/sales/${sale.id}`)} className='cursor-pointer'>
                <TableHead>{startIndex + i + 1}</TableHead>
                <TableCell>
                  <span className='inline-flex items-center gap-2'>
                    {sale.status ?? 'N/A'}
                    <RowSyncStatus status={sale.sync_status} />
                  </span>
                </TableCell>
                <TableCell>{sale.date ?? formatDate(sale.created_at)}</TableCell>
                <TableCell>{sale.note ? String(sale.note).slice(0, 10) : '-'}</TableCell>
                <TableCell> {totalPrice.toLocaleString()}</TableCell>
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
