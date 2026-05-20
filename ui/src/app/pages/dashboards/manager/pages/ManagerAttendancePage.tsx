import { ManagerPageShell } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchAttendanceQuery } from '@/app/store/features/branch';
import { AttendanceRecordItem } from './components/attendance/AttendanceRecordItem';
import { AttendanceSummaryCards } from './components/attendance/AttendanceSummaryCards';
import { resolveList } from './components/manager-page-utils';

export const ManagerAttendancePage = () => {
  const { data, isLoading } = useBranchAttendanceQuery();
  const attendance = resolveList(data, 'attendance');
  const presentCount = attendance.filter((row: any) => row.status === 'present' || row.status === 'Present').length;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Attendance' description='Track branch staff attendance and check-ins.'>
        <AttendanceSummaryCards
          presentCount={presentCount}
          totalRecords={attendance.length}
          lastCheckIn={attendance[0]?.time ?? 'N/A'}
        />

        <div className='space-y-3'>
          {attendance.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No attendance records are available for this branch yet.</p>
          ) : (
            attendance
              .slice(0, 6)
              .map((record: any) => (
                <AttendanceRecordItem
                  key={record.id ?? `${record.employee}-${record.date}`}
                  name={record.employee ?? record.staffName ?? 'Staff member'}
                  status={record.status ?? 'Unknown status'}
                />
              ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
