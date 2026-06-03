import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type AttendancePanelProps = {
  attendance: any[];
};

const formatDate = (value: unknown) => {
  if (!value) return '—';
  const date = new Date(value as string);
  return Number.isNaN(date.getTime())
    ? String(value)
    : date.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
};

export const AttendancePanel = ({ attendance }: AttendancePanelProps) => {
  const records = Array.isArray(attendance) ? attendance : [];
  const presentCount = records.filter(
    (record) => String(record.status || record.attendance_status || '').toLowerCase() === 'present',
  ).length;
  const absentCount = records.filter(
    (record) => String(record.status || record.attendance_status || '').toLowerCase() === 'absent',
  ).length;
  const lateCount = records.filter(
    (record) => String(record.status || record.attendance_status || '').toLowerCase() === 'late',
  ).length;
  const total = records.length;

  return (
    <div className='space-y-6'>
      <div className='grid gap-4 md:grid-cols-3'>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Total records</CardTitle>
            <CardDescription>Attendance snapshots in the selected timeframe.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{total}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Present</CardTitle>
            <CardDescription>Employees who checked in on time.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{presentCount}</p>
          </CardContent>
        </Card>
        <Card className='rounded-3xl border border-border/70 bg-card p-4'>
          <CardHeader>
            <CardTitle>Absent / Late</CardTitle>
            <CardDescription>Missed shifts and late arrivals.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{absentCount + lateCount}</p>
          </CardContent>
        </Card>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card overflow-hidden'>
        <CardHeader>
          <CardTitle>Attendance history</CardTitle>
          <CardDescription>Review recent employee check-ins and shift logs.</CardDescription>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Employee</TableHead>
                <TableHead>Branch</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Check-in</TableHead>
                <TableHead>Check-out</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {records.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className='text-center py-10 text-muted-foreground'>
                    No attendance records available.
                  </TableCell>
                </TableRow>
              ) : (
                records.slice(0, 12).map((record, index) => {
                  const status = String(record.status || record.attendance_status || 'Unknown').toLowerCase();
                  const label =
                    status === 'present'
                      ? 'success'
                      : status === 'late'
                        ? 'secondary'
                        : status === 'absent'
                          ? 'destructive'
                          : 'outline';
                  return (
                    <TableRow key={record.id ?? index}>
                      <TableCell>{formatDate(record.date || record.created_at || record.logged_at)}</TableCell>
                      <TableCell>
                        {record.employee?.name || record.user?.firstname
                          ? `${record.user?.firstname || ''} ${record.user?.lastname || ''}`.trim()
                          : record.employee_name || record.worker_name || '—'}
                      </TableCell>
                      <TableCell>
                        {record.branch?.name || record.business_branch?.name || record.location || '—'}
                      </TableCell>
                      <TableCell>
                        <Badge variant={label as any}>{String(status).toUpperCase()}</Badge>
                      </TableCell>
                      <TableCell>{formatDate(record.clock_in || record.time_in || record.check_in)}</TableCell>
                      <TableCell>{formatDate(record.clock_out || record.time_out || record.check_out)}</TableCell>
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
