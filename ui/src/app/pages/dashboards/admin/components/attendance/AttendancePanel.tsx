import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';

type AttendancePanelProps = {
  attendances: any[];
  absentCount: number;
  presentCount: number;
};

export const AttendancePanel = ({ attendances, absentCount, presentCount }: AttendancePanelProps) => {
  const navigate = useNavigate();
  const total = attendances.length;
  const formatted = (payment_date: any) => format(new Date(payment_date), 'dd MMM yyyy');

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
            <CardTitle>Absent </CardTitle>
            <CardDescription>Missed shifts and late arrivals.</CardDescription>
          </CardHeader>
          <CardContent>
            <p className='text-3xl font-semibold'>{absentCount}</p>
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
              {total === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className='text-center py-10 text-muted-foreground'>
                    No attendance records available.
                  </TableCell>
                </TableRow>
              ) : (
                attendances?.map((record, index) => {
                  return (
                    <TableRow key={record.id ?? index} onClick={() => navigate(`/admin/attendance/${record.id}`)}>
                      <TableCell>{formatted(record.check_in || record.created_at)}</TableCell>
                      <TableCell>
                        {`${record.worker?.user?.firstname || ''} ${record.worker?.user?.lastname || ''}`.trim() ||
                          record.worker?.user?.email ||
                          '—'}
                      </TableCell>
                      <TableCell>{record.worker?.user?.business_branch?.name || '—'}</TableCell>
                      <TableCell>
                        <Badge variant={record.status === 'present' ? 'secondary' : ('' as any)}>{record.status}</Badge>
                      </TableCell>
                      <TableCell>{formatted(record.check_in ?? record.created_at)}</TableCell>
                      <TableCell>{formatted(record.check_out ?? record.created_at)}</TableCell>
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
