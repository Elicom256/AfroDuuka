import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, Upload, CheckCircle, Clock } from 'lucide-react';
import { toast } from 'sonner';

export const TaxInvoicesTable = ({ invoices, onSubmitToUra, onDelete }: any) => {
  const handleSubmit = async (id: string) => {
    try { await onSubmitToUra(id).unwrap(); toast.success('Submitted to URA'); } catch { toast.error('Failed'); }
  };
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
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
        {invoices.map((inv: any) => (
          <TableRow key={inv.id}>
            <TableCell className='font-medium'>{inv.invoice_number}</TableCell>
            <TableCell>#{inv.sale_id}</TableCell>
            <TableCell>UGX {Number(inv.total_amount).toLocaleString()}</TableCell>
            <TableCell>UGX {Number(inv.vat_amount).toLocaleString()}</TableCell>
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
  );
};
