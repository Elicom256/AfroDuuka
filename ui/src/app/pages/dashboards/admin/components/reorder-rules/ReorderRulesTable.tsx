import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, CheckCircle, XCircle } from 'lucide-react';
import { toast } from 'sonner';
import { PaginationComponent } from '@/app/utils/Pagination';

export const ReorderRulesTable = ({ rules, onDelete }: any) => {
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const totalPages = Math.ceil((rules?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedRules = rules?.slice(startIndex, startIndex + itemsPerPage);

  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Product</TableHead>
            <TableHead>Reorder Qty</TableHead>
            <TableHead>Supplier</TableHead>
            <TableHead>Auto-Approve</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedRules?.map((r: any) => (
            <TableRow key={r.id}>
              <TableCell className='font-medium'>{r.product?.name || '—'}</TableCell>
              <TableCell>{r.reorder_quantity}</TableCell>
              <TableCell>{r.preferred_supplier?.user?.firstname || 'Any'}</TableCell>
              <TableCell>
                {r.auto_approve
                  ? <Badge variant='default'><CheckCircle className='h-3 w-3 mr-1' /> Yes</Badge>
                  : <Badge variant='secondary'><XCircle className='h-3 w-3 mr-1' /> No</Badge>}
              </TableCell>
              <TableCell>
                <Button variant='ghost' size='icon' onClick={() => handleDelete(r.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
