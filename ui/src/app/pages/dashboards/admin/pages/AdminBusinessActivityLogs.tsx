import { useBusinessUsersQuery } from '@/app/store/features/auth/authQuery';
import { useGetAdminBusinessActivityLogsQuery } from '@/app/store/features/business/admin/businessActivityLogsQuery';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { ActivityLogsPanel } from '../components/activity-logs/ActivityLogsPanel';

export const AdminBusinessActivityLogs = () => {
  const [param, setParam] = useState({
    page: 1,
    per_page: 7,
    user_id: undefined as number | undefined,
    action: undefined as string | undefined,
  });

  const { data: doers, isLoading: fetchingUsers } = useBusinessUsersQuery();
  const { data, isLoading } = useGetAdminBusinessActivityLogsQuery(param);

  if (isLoading || fetchingUsers) return <PageLoadingState />;

  const users = doers?.data ?? [];
  const logs = data?.logs?.data ?? [];
  const total = data?.total;
  const distinct = data?.distinct;
  // console.log('activity logs==>', doers);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Business Activity Logs</h1>
        <p className='text-muted-foreground'>Audit recent admin actions and system events.</p>
      </div>

      {/* FILTERS */}
      <div className='flex gap-4'>
        {/* USER */}
        <Select
          value={param.user_id?.toString() || 'all'}
          onValueChange={(value: any) =>
            setParam((prev) => ({
              ...prev,
              user_id: value === 'all' ? undefined : Number(value),
            }))
          }
        >
          <SelectTrigger className='w-55'>
            <SelectValue placeholder='Filter by user' />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value='all'>All users</SelectItem>
            {users.map((user: any) => (
              <SelectItem key={user.id} value={user.id.toString()}>
                {`${user.firstname}  ${user.firstname}`}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* ACTION */}
        <Select
          value={param.action || 'all'}
          onValueChange={(value: any) =>
            setParam((prev) => ({
              ...prev,
              action: value === 'all' ? undefined : value,
            }))
          }
        >
          <SelectTrigger className='w-55'>
            <SelectValue placeholder='Filter by action' />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value='all'>All actions</SelectItem>
            {['TAX_CREATED', 'TAX_UPDATED', 'TAX_PAYMENT_CREATED', 'TAX_DELETED'].map((action) => (
              <SelectItem key={action} value={action}>
                {action.replaceAll('_', ' ')}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* TABLE */}
      <ActivityLogsPanel logs={logs} total={total} distinct={distinct} />
    </div>
  );
};
