import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, CheckCircle, XCircle } from 'lucide-react';
import { toast } from 'sonner';

export const PaymentGatewaysTable = ({ gateways, onDelete }: any) => {
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Provider</TableHead>
          <TableHead>Status</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {gateways.map((g: any) => (
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
  );
};
