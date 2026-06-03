import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type ActivityLogsPanelProps = {
  logs: any[];
};

const formatTime = (value: unknown) => {
  if (!value) return '—';
  const date = new Date(value as string);
  return Number.isNaN(date.getTime())
    ? String(value)
    : date.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
};

export const ActivityLogsPanel = ({ logs }: ActivityLogsPanelProps) => {
  const records = Array.isArray(logs) ? logs : [];
  const total = records.length;
  const successCount = records.filter(
    (record) => String(record.status || record.result || '').toLowerCase() === 'success',
  ).length;
  const failureCount = records.filter((record) =>
    ['error', 'failed', 'failure'].includes(String(record.status || record.result || '').toLowerCase()),
  ).length;
  const activeUsers = new Set(records.map((record) => record.user?.id || record.actor_id || record.user_id)).size;

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
            <p className='text-3xl font-semibold'>{successCount}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Distinct users</CardTitle>
            <CardDescription>Individuals who triggered activity log entries.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{activeUsers}</p>
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
              {records.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className='text-center py-10 text-muted-foreground'>
                    No activity log entries to display.
                  </TableCell>
                </TableRow>
              ) : (
                records.slice(0, 12).map((record, index) => {
                  const status = String(record.status || record.result || 'unknown').toLowerCase();
                  const variant =
                    status === 'success'
                      ? 'default'
                      : status === 'failed' || status === 'error'
                        ? 'destructive'
                        : 'outline';
                  return (
                    <TableRow key={record.id ?? index}>
                      <TableCell>{formatTime(record.created_at || record.timestamp || record.logged_at)}</TableCell>
                      <TableCell>{record.user?.name || record.actor?.name || record.user_name || 'System'}</TableCell>
                      <TableCell>{record.action || record.event || record.activity || '—'}</TableCell>
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
