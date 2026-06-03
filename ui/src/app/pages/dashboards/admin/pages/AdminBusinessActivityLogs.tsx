import { useGetAdminBusinessActivityLogsQuery } from '@/app/store/features/business/admin/businessActivityLogsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ActivityLogsPanel } from '../components/activity-logs/ActivityLogsPanel';

export const AdminBusinessActivityLogs = () => {
  const { data, isLoading } = useGetAdminBusinessActivityLogsQuery();

  if (isLoading) return <PageLoadingState />;

  const logs = Array.isArray(data) ? data : (data?.activity_logs ?? data?.logs ?? data?.records ?? []);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Business Activity Logs</h1>
        <p className='text-muted-foreground'>Audit recent admin actions and system events.</p>
      </div>
      <ActivityLogsPanel logs={logs} />
    </div>
  );
};
