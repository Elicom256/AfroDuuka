import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, Wifi, Bluetooth, Cable } from 'lucide-react';
import { toast } from 'sonner';

const typeIcons: Record<string, any> = { bluetooth: Bluetooth, usb: Cable, network: Wifi };

export const PrintersTable = ({ printers, onDelete }: any) => {
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Name</TableHead>
          <TableHead>Type</TableHead>
          <TableHead>Branch</TableHead>
          <TableHead>Default</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {printers.map((p: any) => {
          const TypeIcon = typeIcons[p.type] || Cable;
          return (
            <TableRow key={p.id}>
              <TableCell className='font-medium'>{p.name}</TableCell>
              <TableCell>
                <div className='flex items-center gap-2'>
                  <TypeIcon className='h-4 w-4 text-muted-foreground' />
                  <span className='capitalize'>{p.type}</span>
                </div>
              </TableCell>
              <TableCell>{p.business_branch?.name || '—'}</TableCell>
              <TableCell>{p.is_default ? <Badge variant='default'>Default</Badge> : <Badge variant='secondary'>—</Badge>}</TableCell>
              <TableCell>
                <Button variant='ghost' size='icon' onClick={() => handleDelete(p.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
              </TableCell>
            </TableRow>
          );
        })}
      </TableBody>
    </Table>
  );
};
