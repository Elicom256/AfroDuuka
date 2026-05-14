import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit3, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';

export type WorkerItem = {
  id: number;
  name?: string;
  email?: string;
  phone?: string;
  role?: string;
};

type WorkersTableProps = {
  workers: any;
  onEdit: (worker: WorkerItem) => void;
  onDelete: (worker: WorkerItem) => void;
  isLoading: boolean;
  isDeleting?: boolean;
};

export const WorkersTable = ({ workers, onEdit, onDelete, isLoading, isDeleting }: WorkersTableProps) => {
  if (isLoading) {
    return (
      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Phone</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {Array.from({ length: 4 }).map((_, index) => (
              <TableRow key={index} className='opacity-80'>
                <TableCell className='h-10 bg-muted/50' />
                <TableCell className='h-10 bg-muted/50' />
                <TableCell className='h-10 bg-muted/50' />
                <TableCell className='h-10 bg-muted/50' />
                <TableCell className='h-10 bg-muted/50' />
                <TableCell className='h-10 bg-muted/50' />
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    );
  }
  console.log('user yesss=>', workers);

  if (workers?.length === 0) {
    return (
      <Card className='rounded-3xl border border-dashed border-border/50 bg-muted p-6 text-center'>
        <p className='text-lg font-semibold'>No workers available</p>
        <Link to='/' className='mt-2 text-sm text-muted-foreground'>
          Add worker accounts to populate the table.
        </Link>
      </Card>
    );
  }

  return (
    <Card className='overflow-hidden'>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Phone</TableHead>
            <TableHead>Branch</TableHead>
            <TableHead>Role</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {workers &&
            workers.map((worker: any) => (
              <TableRow key={worker.id}>
                <TableCell>{worker.name || '—'}</TableCell>
                <TableCell>{worker.email || '—'}</TableCell>
                <TableCell>{worker.phone || '—'}</TableCell>
                <TableCell>{worker.business_branch.name || '—'}</TableCell>
                <TableCell>{(worker?.role as any)?.name || '—'}</TableCell>
                <TableCell>
                  <div className='flex flex-wrap gap-2'>
                    <Button size='icon-sm' variant='outline' onClick={() => onEdit(worker)}>
                      <Edit3 className='h-4 w-4' />
                    </Button>
                    <Button size='icon-sm' variant='destructive' onClick={() => onDelete(worker)} disabled={isDeleting}>
                      <Trash2 className='h-4 w-4' />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>
    </Card>
  );
};
