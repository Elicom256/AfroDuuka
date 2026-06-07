import { useRecordEmployeeattendanceMutation } from '@/app/store/features/business/admin/attendanceQuery';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Edit3, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

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
  const [recordAttendance, { isLoading: recording }] = useRecordEmployeeattendanceMutation();
  const [session, setSession] = useState<'morning' | 'afternoon' | 'evening'>('morning');
  const [allPresent, setAllPresent] = useState<boolean>(false);
  const [selected, setSelected] = useState<Record<number, boolean>>({});

  const navigate = useNavigate();

  const submitAttendance = async () => {
    const attendances = (workers || []).map((w: any) => ({
      worker_id: w.id,
      session,
      status: selected[w.id] ? 'present' : 'absent',
    }));

    try {
      const res = await recordAttendance({ attendances }).unwrap();
      console.log('response==>', res.message);
      toast.success(res.message ?? 'Attendance submitted successfully');
      setAllPresent(false);
      setSelected({});
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to submit attendance');
      console.error('record attendance error', err);
    }
  };

  if (recording) {
    return <PageLoadingState />;
  }

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
      <div className='flex items-center justify-between gap-4 p-4'>
        <div className='flex items-center gap-3'>
          <label className='flex items-center gap-2'>
            <input
              type='checkbox'
              checked={allPresent}
              onChange={(e) => {
                const checked = e.target.checked;
                setAllPresent(checked);
                const map: Record<number, boolean> = {};
                (workers || []).forEach((w: any) => (map[w.id] = checked));
                setSelected(map);
              }}
            />
            <span className='text-sm'>Mark all as present</span>
          </label>

          <div className='space-y-1'>
            <span className='text-sm text-muted-foreground'>Session</span>
            <Select value={session} onValueChange={(value: 'morning' | 'afternoon' | 'evening') => setSession(value)}>
              <SelectTrigger className='w-44'>
                <SelectValue placeholder='Select session' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='morning'>Morning</SelectItem>
                <SelectItem value='afternoon'>Afternoon</SelectItem>
                <SelectItem value='evening'>Evening</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div>
          <Button onClick={submitAttendance}>Submit Attendance</Button>
        </div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead></TableHead>
            <TableHead>Emp Code</TableHead>
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
              <TableRow key={worker.id} onClick={() => navigate(`/admin/workers/${worker.id}`)}>
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <input
                    type='checkbox'
                    checked={!!selected[worker.id]}
                    onChange={(e) => {
                      const checked = e.target.checked;
                      setSelected((s) => ({ ...s, [worker.id]: checked }));
                      if (!checked) setAllPresent(false);
                    }}
                  />
                </TableCell>
                <TableCell>{worker.employee_code || '—'}</TableCell>
                <TableCell>{`${worker.user.firstname ?? '-'} ${worker.user.lastname}`}</TableCell>
                <TableCell>{worker.user.email || '—'}</TableCell>
                <TableCell>{worker.user.phone || '—'}</TableCell>
                <TableCell>{worker.user.business_branch.name ?? '—'}</TableCell>
                <TableCell>{(worker?.user.role as any)?.name || '—'}</TableCell>
                <TableCell>
                  <div className='flex flex-wrap gap-2'>
                    <Button
                      size='icon-sm'
                      variant='outline'
                      onClick={(e) => {
                        e.stopPropagation();
                        onEdit(worker);
                      }}
                    >
                      <Edit3 className='h-4 w-4' />
                    </Button>
                    <Button
                      size='icon-sm'
                      variant='destructive'
                      onClick={(e) => {
                        e.stopPropagation();
                        onDelete(worker);
                      }}
                      disabled={isDeleting}
                    >
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
