import { useDeleteAdminBusinessActivityLogMutation } from '@/app/store/features/business/admin/businessActivityLogsQuery';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { LoadingState } from '@/utils/LoadingState';
import { format } from 'date-fns';
import { Archive } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';

type ActivityLogsPanelProps = {
  logs: any[];
  total: number;
  distinct: number;
};

export const ActivityLogsPanel = ({ logs, total, distinct }: ActivityLogsPanelProps) => {
  const formatted = (payment_date: any) => format(new Date(payment_date), 'dd MMM yyyy');
  const [destroy, { isLoading }] = useDeleteAdminBusinessActivityLogMutation();
  const [item, setItem] = useState<string>('');
  const handleDelete = async (id: string) => {
    setItem(id);
    const res = await destroy(id).unwrap();
    if (res) {
      toast.success(res.message ?? 'Archieved Log');
    }
  };
  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card'>
          <CardHeader>
            <CardTitle>Total activity</CardTitle>
            <CardDescription>Logged business operations from the admin portal.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-xl font-semibold'>{total}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card '>
          <CardHeader>
            <CardTitle>Successful events</CardTitle>
            <CardDescription>Completed actions and confirmations.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-xl font-semibold'>{total}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card '>
          <CardHeader>
            <CardTitle>Distinct users</CardTitle>
            <CardDescription>Individuals who triggered activity log entries.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-xl font-semibold'>{distinct}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader>
          <CardAction>
            <Link className='text-violet-500 hover:underline' to='/'>
              Archieved
            </Link>
          </CardAction>
          <CardTitle>Business activity logs</CardTitle>
          <CardDescription>Recent backend actions, events, and audit entries.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>LogID</TableHead>
                <TableHead>Timestamp</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Action</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {logs.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className='text-center py-10 text-muted-foreground'>
                    No activity log entries to display.
                  </TableCell>
                </TableRow>
              ) : (
                logs.map((record) => {
                  return (
                    <TableRow key={record.id}>
                      <TableCell>{record.id}</TableCell>
                      <TableCell>{formatted(record.created_at)}</TableCell>
                      <TableCell>
                        {record.user?.username || record.actor?.name || record.user_name || 'System'}
                      </TableCell>
                      <TableCell>{record.action ?? '—'}</TableCell>
                      <TableCell>
                        {record.description.length > 12 ? `${record.description.slice(0, 12)}...` : record.description}
                      </TableCell>
                      <TableCell>
                        {isLoading && item === record.id ? (
                          <LoadingState />
                        ) : (
                          <Archive className='text-gray-400 cursor-pointer' onClick={() => handleDelete(record.id)} />
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
};
