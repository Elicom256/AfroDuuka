import { useGetAdminAttendanceQuery } from '@/app/store/features/business/admin/attendanceQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AttendancePanel } from '../components/attendance/AttendancePanel';

export const AdminAttendancePage = () => {
  const { data, isLoading } = useGetAdminAttendanceQuery();

  if (isLoading) return <PageLoadingState />;

  const attendance = Array.isArray(data) ? data : (data?.attendance ?? data?.records ?? []);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Attendance</h1>
        <p className='text-muted-foreground'>Monitor employee presence, attendance trends, and shift records.</p>
      </div>
      <AttendancePanel attendance={attendance} />
    </div>
  );
};
