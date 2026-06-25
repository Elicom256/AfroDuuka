import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Check, X } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

type AttendancePanelProps = {
  attendances: any[];
  absentCount: number;
  presentCount: number;
  employees: any[];
};

export const AttendancePanel = ({ attendances, absentCount, presentCount, employees }: AttendancePanelProps) => {
  const navigate = useNavigate();
  const total = attendances.length;
  const headers = ['Name', 'Branch', 'Status', 'Attendance'];
  // const attendanceheaders = ['Name', 'Branch', 'Status',];
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
                {headers?.map((header, i) => (
                  <TableHead key={i}>{header}</TableHead>
                ))}
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
                employees?.map((worker, index) => {
                  return (
                    <TableRow key={worker.id ?? index} onClick={() => navigate(`/admin/attendance/${worker.id}`)}>
                      {/* <TableCell>{formatted(worker.check_in || worker.created_at)}</TableCell> */}
                      <TableCell>
                        {`${worker?.user?.firstname || ''} ${worker?.user?.lastname || ''}`.trim() ||
                          worker.worker?.user?.email ||
                          '—'}
                      </TableCell>
                      <TableCell>{worker?.user?.business_branch?.name || '—'}</TableCell>
                      <TableCell>
                        <Badge variant={worker.status === 'present' ? 'secondary' : ('' as any)}>{worker.status}</Badge>
                      </TableCell>
                      <TableCell className='flex gap-2'>
                        {worker?.attendances?.map((record: any) => (
                          <div className=''>
                            {['present', 'late'].includes(record?.status) ? (
                              <Check className='text-green-400' size={15} />
                            ) : (
                              ['absent', 'late', 'excused', null].includes(record?.status) && (
                                <X className='text-red-400' size={15} />
                              )
                            )}
                          </div>
                        ))}
                      </TableCell>
                      {/* <TableCell>{formatted(worker.check_in ?? worker.created_at)}</TableCell> */}
                      {/* <TableCell>{formatted(worker.check_out ?? worker.created_at)}</TableCell> */}
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
