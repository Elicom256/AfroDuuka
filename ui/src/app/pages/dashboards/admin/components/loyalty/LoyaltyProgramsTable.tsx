import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export const LoyaltyProgramsTable = ({ programs, onDelete }: any) => {
  const handleDelete = async (id: string) => {
    try { await onDelete(id).unwrap(); toast.success('Deleted'); } catch { toast.error('Failed'); }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Name</TableHead>
          <TableHead>Type</TableHead>
          <TableHead>Points/Currency</TableHead>
          <TableHead>Redemption Rate</TableHead>
          <TableHead>Expiry</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {programs.map((p: any) => (
          <TableRow key={p.id}>
            <TableCell className='font-medium'>{p.name}</TableCell>
            <TableCell className='capitalize'>{p.type}</TableCell>
            <TableCell>{p.points_per_currency}</TableCell>
            <TableCell>{p.redemption_rate}</TableCell>
            <TableCell>{p.expiry_days ? `${p.expiry_days} days` : 'Never'}</TableCell>
            <TableCell>
              <Button variant='ghost' size='icon' onClick={() => handleDelete(p.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
};
