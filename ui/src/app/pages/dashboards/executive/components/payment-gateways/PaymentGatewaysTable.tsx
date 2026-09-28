import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, CheckCircle, XCircle } from 'lucide-react';
import { toast } from 'sonner';
import { PaginationComponent } from '@/app/utils/Pagination';

export const PaymentGatewaysTable = ({ gateways, onDelete }: any) => {
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const totalPages = Math.ceil((gateways?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedGateways = gateways?.slice(startIndex, startIndex + itemsPerPage);

  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Provider</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedGateways?.map((g: any) => (
            <TableRow key={g.id}>
              <TableCell className='font-medium capitalize'>{g.provider.replace(/_/g, ' ')}</TableCell>
              <TableCell>
                <Badge variant={g.is_active ? 'default' : 'secondary'}>
                  {g.is_active ? <CheckCircle className='h-3 w-3 mr-1' /> : <XCircle className='h-3 w-3 mr-1' />}
                  {g.is_active ? 'Active' : 'Inactive'}
                </Badge>
              </TableCell>
              <TableCell>
                <Button variant='ghost' size='icon' onClick={() => handleDelete(g.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
