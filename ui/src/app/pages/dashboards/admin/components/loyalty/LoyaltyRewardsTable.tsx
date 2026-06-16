import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export const LoyaltyRewardsTable = ({ rewards, onDelete }: any) => {
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Name</TableHead>
          <TableHead>Description</TableHead>
          <TableHead>Points Required</TableHead>
          <TableHead>Stock</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {rewards.map((r: any) => (
          <TableRow key={r.id}>
            <TableCell className='font-medium'>{r.name}</TableCell>
            <TableCell className='text-muted-foreground max-w-[200px] truncate'>{r.description || '—'}</TableCell>
            <TableCell>{r.points_required}</TableCell>
            <TableCell>{r.stock ?? 'Unlimited'}</TableCell>
            <TableCell>
              <Button variant='ghost' size='icon' onClick={() => handleDelete(r.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
};
