import { CalendarCheck } from 'lucide-react';
import { SectionCard } from '../manager-page-shell';

export const AttendanceSummaryCards = ({
  presentCount,
  totalRecords,
  lastCheckIn,
}: {
  presentCount: number;
  totalRecords: number;
  lastCheckIn: string;
}) => (
  <div className='grid gap-4 md:grid-cols-3'>
    <SectionCard title='Present today' value={presentCount} icon={<CalendarCheck className='h-5 w-5' />} />
    <SectionCard title='Total records' value={totalRecords} icon={<CalendarCheck className='h-5 w-5' />} />
    <SectionCard title='Last check-in' value={lastCheckIn} icon={<CalendarCheck className='h-5 w-5' />} />
  </div>
);
