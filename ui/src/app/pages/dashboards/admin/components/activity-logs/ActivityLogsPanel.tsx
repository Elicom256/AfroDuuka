import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';

type ActivityLogsPanelProps = {
  logs: any[];
  total: number;
  distinct: number;
};

export const ActivityLogsPanel = ({ logs, total, distinct }: ActivityLogsPanelProps) => {
  const formatted = (payment_date: any) => format(new Date(payment_date), 'dd MMM yyyy');
  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total activity</CardTitle>
            <CardDescription>Logged business operations from the admin portal.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{total}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Successful events</CardTitle>
            <CardDescription>Completed actions and confirmations.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{total}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Distinct users</CardTitle>
            <CardDescription>Individuals who triggered activity log entries.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{distinct}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader>
          <CardTitle>Business activity logs</CardTitle>
          <CardDescription>Recent backend actions, events, and audit entries.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Timestamp</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Action</TableHead>
                <TableHead>Resource</TableHead>
                <TableHead>Result</TableHead>
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
                logs.map((record, index) => {
                  const status = String(record.status || record.result || 'unknown').toLowerCase();
                  const variant =
                    status === 'success'
                      ? 'default'
                      : status === 'failed' || status === 'error'
                        ? 'destructive'
                        : 'outline';
                  return (
                    <TableRow key={record.id ?? index}>
                      <TableCell>{formatted(record.created_at)}</TableCell>
                      <TableCell>
                        {record.user?.username || record.actor?.name || record.user_name || 'System'}
                      </TableCell>
                      <TableCell>{record.action ?? '—'}</TableCell>
                      <TableCell>{record.resource || record.module || record.target || '—'}</TableCell>
                      <TableCell>
                        <Badge variant={variant as any}>{String(status).toUpperCase()}</Badge>
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
