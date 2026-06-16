import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export const CurrencyRatesTable = ({ rates, onDelete }: any) => {
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Base</TableHead>
          <TableHead>Target</TableHead>
          <TableHead>Rate</TableHead>
          <TableHead>Source</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {rates.map((r: any) => (
          <TableRow key={r.id}>
            <TableCell>{r.base_currency}</TableCell>
            <TableCell className='font-medium'>{r.target_currency}</TableCell>
            <TableCell>{Number(r.rate).toLocaleString()}</TableCell>
            <TableCell className='capitalize'>{r.source?.replace(/_/g, ' ')}</TableCell>
            <TableCell>
              <Button variant='ghost' size='icon' onClick={() => handleDelete(r.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
};
