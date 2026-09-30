import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useGetActivityLogsQuery } from '@/app/store/features/business/executive/activityLogQuery';
import { Activity } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';

export const RecentActivity = () => {
  const { data, isLoading } = useGetActivityLogsQuery({ per_page: 5 });
  const logs = data?.data ?? [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className='h-9 w-full' />
          ))}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Activity className='h-4 w-4 text-primary' />
          Recent activity
        </CardTitle>
        <CardDescription>Latest actions across your business</CardDescription>
      </CardHeader>
      <CardContent>
        {logs.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No recent activity.</p>
        ) : (
          <div className='space-y-2'>
            {logs.map((log) => (
              <div key={log.id} className='flex items-start justify-between gap-3 rounded-lg border border-border/50 px-3 py-2'>
                <div className='min-w-0'>
                  <p className='truncate text-sm font-medium'>{log.description ?? 'Activity'}</p>
                  <p className='text-xs text-muted-foreground'>
                    {log.causer?.name ?? 'System'}
                    {log.created_at && ` · ${formatDistanceToNow(new Date(log.created_at), { addSuffix: true })}`}
                  </p>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
