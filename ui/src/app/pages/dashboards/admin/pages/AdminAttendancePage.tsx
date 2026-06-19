import { useEmployeeAttendancesQuery } from '@/app/store/features/business/admin/attendanceQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AttendancePanel } from '../components/attendance/AttendancePanel';
import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';

export const AdminAttendancePage = () => {
  const { data, isLoading } = useEmployeeAttendancesQuery();
  const { data: workers, isLoading: fetching } = useGetWorkersInfoQuery();

  const attendances = data?.attendances?.data ?? [];
  const absentCount = data?.absentCount;
  const presentCount = data?.presentCount;
  const employees = workers?.workers;
  console.log('attendances==>', employees);
  if (isLoading || fetching) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Attendance</h1>
        <p className='text-muted-foreground'>Monitor employee presence, attendance trends, and shift records.</p>
      </div>
      <AttendancePanel
        attendances={attendances}
        presentCount={presentCount}
        absentCount={absentCount}
        employees={employees}
      />
    </div>
  );
};
