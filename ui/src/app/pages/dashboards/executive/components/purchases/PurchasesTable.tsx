import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';
import { useRolePermissions } from '@/lib/useRolePermissions';
import { Badge } from '@/components/ui/badge';

interface PurchasesTableProps {
  purchases: any[];
  products: any[];
}

export const PurchasesTable = ({ purchases }: PurchasesTableProps) => {
  const { currency } = useCurrency();
  const navigate = useNavigate();
  const [currentPage, setCurrentPage] = useState(1);
  useRolePermissions();
  const itemsPerPage = 10;

  const totalPages = Math.ceil((purchases?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedPurchases = purchases?.slice(startIndex, startIndex + itemsPerPage);

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            <TableHead>Supplier ID</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Note</TableHead>
            <TableHead>Amount ({currency})</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedPurchases?.length > 0 ? (
            paginatedPurchases.map((purchase, i) => {
              const totalAmount = Number(purchase.total_amount ?? 0);
              const status = purchase.status ?? 'completed';
              const isPending = status === 'pending';

              return (
                <TableRow
                  key={purchase.id}
                  onClick={() => navigate(`/dashboard/purchases/${purchase.id}`)}
                  className='cursor-pointer'
                >
                  <TableCell>{startIndex + i + 1}</TableCell>
                  <TableCell>{purchase.supplier_id ?? 'N/A'}</TableCell>
                  <TableCell>{purchase.date ?? format(new Date(purchase.created_at), 'PPP') ?? '-'}</TableCell>
                  <TableCell>
                    <Badge variant={isPending ? 'secondary' : 'default'}>{isPending ? 'Pending' : 'Completed'}</Badge>
                  </TableCell>
                  <TableCell>{purchase.note ? purchase.note.slice(0, 10) : '-'}</TableCell>
                  <TableCell>{totalAmount.toLocaleString()}</TableCell>
                </TableRow>
              );
            })
          ) : (
            <TableRow>
              <TableCell colSpan={6}>
                <p className='text-center py-3 hover:underline'>You haven't recorded any purchase yet!</p>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
