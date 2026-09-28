import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { CalendarCheck, Clock, CheckCircle2, XCircle } from 'lucide-react';

export const StaffAttendance = () => {
  const { data: userData, isLoading } = useLoggedinUserQuery();

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
        </CardContent>
      </Card>
    );
  }

  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <CalendarCheck className='h-4 w-4 text-emerald-500' />
          Today's Attendance
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-3'>
        <div className='flex items-center gap-2 text-sm text-muted-foreground'>
          <Clock className='h-3.5 w-3.5' />
          {today}
        </div>
        <div className='flex items-center gap-3 rounded-lg border border-border/50 px-3 py-2.5'>
          <div className='flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/30'>
            <CheckCircle2 className='h-4 w-4 text-emerald-600 dark:text-emerald-400' />
          </div>
          <div className='flex-1'>
            <p className='text-sm font-medium'>Checked In</p>
            <p className='text-xs text-muted-foreground'>08:00 AM</p>
          </div>
          <span className='text-xs font-medium text-emerald-600 dark:text-emerald-400'>On time</span>
        </div>
        <div className='flex items-center gap-3 rounded-lg border border-border/50 px-3 py-2.5'>
          <div className='flex h-9 w-9 items-center justify-center rounded-full bg-muted'>
            <Clock className='h-4 w-4 text-muted-foreground' />
          </div>
          <div className='flex-1'>
            <p className='text-sm font-medium'>Shift End</p>
            <p className='text-xs text-muted-foreground'>05:00 PM</p>
          </div>
          <span className='text-xs text-muted-foreground'>Pending</span>
        </div>
      </CardContent>
    </Card>
  );
};
