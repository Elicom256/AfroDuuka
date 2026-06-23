import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, Upload, CheckCircle, Clock } from 'lucide-react';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';
import { PaginationComponent } from '@/app/utils/Pagination';

export const TaxInvoicesTable = ({ invoices, onSubmitToUra, onDelete }: any) => {
  const { currency } = useCurrency();
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const totalPages = Math.ceil((invoices?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedInvoices = invoices?.slice(startIndex, startIndex + itemsPerPage);

  const handleSubmit = async (id: string) => {
    try { await onSubmitToUra(id).unwrap(); toast.success('Submitted to URA'); } catch { toast.error('Failed'); }
  };
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Invoice #</TableHead>
            <TableHead>Sale</TableHead>
            <TableHead>Amount</TableHead>
            <TableHead>VAT</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedInvoices?.map((inv: any) => (
            <TableRow key={inv.id}>
              <TableCell className='font-medium'>{inv.invoice_number}</TableCell>
              <TableCell>#{inv.sale_id}</TableCell>
              <TableCell>{currency} {Number(inv.total_amount).toLocaleString()}</TableCell>
              <TableCell>{currency} {Number(inv.vat_amount).toLocaleString()}</TableCell>
              <TableCell>
                {inv.submitted_to_ura
                  ? <Badge variant='default'><CheckCircle className='h-3 w-3 mr-1' /> Submitted</Badge>
                  : <Badge variant='secondary'><Clock className='h-3 w-3 mr-1' /> Draft</Badge>}
              </TableCell>
              <TableCell>
                <div className='flex gap-1'>
                  {!inv.submitted_to_ura && (
                    <Button size='sm' variant='outline' onClick={() => handleSubmit(inv.id)}>
                      <Upload className='h-3 w-3 mr-1' /> Submit
                    </Button>
                  )}
                  <Button variant='ghost' size='icon' onClick={() => handleDelete(inv.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
