import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Clock, Plus } from 'lucide-react';
import { AddAttendanceForm } from './AddAttendanceForm';

type Props = {
  attendances: any[];
  attendanceHistory?: { present: number; absent: number };
  workerId: string | number;
  onAttendanceAdded?: () => void;
};

export const AttendanceHistory: React.FC<Props> = ({ attendances, workerId, onAttendanceAdded }) => {
  const [showForm, setShowForm] = useState(false);

  const sortedAttendances = [...attendances].sort(
    (a, b) => new Date(b.check_in).getTime() - new Date(a.check_in).getTime(),
  );

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'present':
        return <Badge variant='default'>Present</Badge>;
      case 'late':
        return (
          <Badge variant='secondary' className='bg-amber-500 hover:bg-amber-500'>
            Late
          </Badge>
        );
      case 'absent':
        return <Badge variant='destructive'>Absent</Badge>;
      default:
        return <Badge variant='outline'>{status}</Badge>;
    }
  };

  return (
    <Card>
      <CardHeader>
        <div className='flex items-center justify-between'>
          <CardTitle className='flex items-center gap-2'>
            <Clock className='h-5 w-5' />
            Attendance History
          </CardTitle>
          <Button onClick={() => setShowForm(!showForm)} variant='outline' size='sm'>
            <Plus className='mr-2 h-4 w-4' />
            {showForm ? 'Hide Form' : 'Add Record'}
          </Button>
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        {showForm && (
          <AddAttendanceForm
            workerId={workerId}
            onSuccess={() => {
              setShowForm(false);
              onAttendanceAdded?.();
            }}
          />
        )}

        {sortedAttendances.length === 0 ? (
          <p className='text-center text-muted-foreground py-8'>No attendance records yet.</p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Session</TableHead>
                <TableHead>Check In</TableHead>
                <TableHead>Check Out</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Remarks</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {sortedAttendances.slice(0, 10).map((att) => (
                <TableRow key={att.id}>
                  <TableCell className='font-medium'>{new Date(att.check_in).toLocaleDateString()}</TableCell>
                  <TableCell className='capitalize'>{att.session}</TableCell>
                  <TableCell>
                    {new Date(att.check_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </TableCell>
                  <TableCell>
                    {att.check_out && att.check_out !== '2026-06-05T00:00:00.000000Z'
                      ? new Date(att.check_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                      : '—'}
                  </TableCell>
                  <TableCell>{getStatusBadge(att.status)}</TableCell>
                  <TableCell className='max-w-xs truncate'>{att.remarks || '—'}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
};
