import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Send, XCircle, ArrowRight } from 'lucide-react';
import { toast } from 'sonner';

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  in_transit: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
  received: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
  cancelled: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
};

export const StockTransfersTable = ({ transfers, onDispatch, onCancel }: any) => {
  const handleDispatch = async (id: string) => {
    try {
      const res = await onDispatch(id).unwrap();
      console.log('response==>', res);
      toast.success(res.message);
    } catch (err: any) {
      console.log('response==>', err);
      toast.error(err?.data?.message || 'Failed');
    }
  };
  const handleCancel = async (id: string) => {
    try {
      await onCancel(id).unwrap();
      toast.success('Cancelled');
    } catch {
      toast.error('Failed');
    }
  };

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>#</TableHead>
          <TableHead>From → To</TableHead>
          <TableHead>Items</TableHead>
          <TableHead>Status</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {transfers.map((t: any, i: number) => (
          <TableRow key={t.id}>
            <TableCell>{i + 1}</TableCell>
            <TableCell>
              <span className='font-medium'>{t.from_branch?.name || '—'}</span>
              <ArrowRight className='h-3 w-3 inline mx-1 text-muted-foreground' />
              <span className='font-medium'>{t.to_branch?.name || '—'}</span>
            </TableCell>
            <TableCell>{t.items?.length || 0}</TableCell>
            <TableCell>
              <Badge className={statusColors[t.status]}>{t.status.replace(/_/g, ' ')}</Badge>
            </TableCell>
            <TableCell>
              <div className='flex gap-1'>
                {t.status === 'draft' && (
                  <>
                    <Button size='sm' variant='outline' onClick={() => handleDispatch(t.id)}>
                      <Send className='h-3 w-3 mr-1' /> Dispatch
                    </Button>
                    <Button size='sm' variant='ghost' onClick={() => handleCancel(t.id)}>
                      <XCircle className='h-3 w-3 text-destructive' />
                    </Button>
                  </>
                )}
              </div>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
};
