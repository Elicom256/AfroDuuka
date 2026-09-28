import { useEmployeeAttendanceQuery } from '@/app/store/features/business/executive/attendanceQuery';
import { useParams } from 'react-router-dom';

export const Attendance = () => {
  const { id } = useParams<{ id: string }>();
  useEmployeeAttendanceQuery(id!, { skip: !id });
//   console.log('employee att==>', data);
  return <div>Attendance</div>;
};
