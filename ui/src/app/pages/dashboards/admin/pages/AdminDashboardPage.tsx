import { useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';
import { Activity, Users } from 'lucide-react';

export const AdminDashboardPage = () => {
  const navigate = useNavigate();
  const { data, isLoading, isError, refetch } = useGetWorkersInfoQuery();

  const workers = useMemo(() => {
    if (Array.isArray(data)) {
      return data;
    }
    if (data && typeof data === 'object' && Array.isArray((data as any).data)) {
      return (data as any).data;
    }
    return [] as any[];
  }, [data]);
  console.log('data==>', data);
  const roleCounts = useMemo<Record<string, number>>(() => {
    return workers.reduce(
      (acc: Record<string, number>, worker: any) => {
        const role = worker.role || 'Unknown';
        acc[role] = (acc[role] || 0) + 1;
        return acc;
      },
      {} as Record<string, number>,
    );
  }, [workers]);

  return (
    <div className='space-y-6'>
      <div className='grid gap-6 xl:grid-cols-[2fr_1fr]'>
        <Card className='overflow-hidden'>
          <CardHeader>
            <CardTitle>Operations overview</CardTitle>
            <CardDescription>Useful metrics for your admin team.</CardDescription>
          </CardHeader>
          <CardContent className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <div className='flex items-center justify-between gap-4'>
                <div>
                  <p className='text-sm font-medium text-muted-foreground'>Workers</p>
                  <p className='mt-2 text-4xl font-semibold'>{isLoading ? '…' : workers.length}</p>
                </div>
                <div className='flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary'>
                  <Users className='h-5 w-5' />
                </div>
              </div>
            </div>

            <div className='rounded-3xl border border-border/70 bg-muted p-6'>
              <div className='flex items-center justify-between gap-4'>
                <div>
                  <p className='text-sm font-medium text-muted-foreground'>Activity</p>
                  <p className='mt-2 text-4xl font-semibold'>{isLoading ? '…' : roleCounts['Admin'] || 0}</p>
                </div>
                <div className='flex h-12 w-12 items-center justify-center rounded-2xl bg-secondary/10 text-secondary'>
                  <Activity className='h-5 w-5' />
                </div>
              </div>
            </div>
          </CardContent>
          <CardFooter className='flex items-center justify-end gap-2'>
            <Button variant='outline' size='sm' onClick={() => navigate('/admin/workers')}>
              View workers
            </Button>
          </CardFooter>
        </Card>

        <Card className='overflow-hidden'>
          <CardHeader>
            <CardTitle>Roles breakdown</CardTitle>
            <CardDescription>Worker counts by role.</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            {isLoading ? (
              <p className='text-sm text-muted-foreground'>Loading roles…</p>
            ) : data?.data.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No workers found yet.</p>
            ) : (
              data.data.map((worker: any, index: number) => (
                <div
                  key={worker.id}
                  className='flex items-center justify-between rounded-2xl border border-border/70 bg-background p-4'
                >
                  <div className='flex items-center gap-1'>
                    <span className='text-sm font-medium'>{worker.username},</span>
                    <span className='text-sm font-medium'>{worker.role.name}</span>
                  </div>
                  <span className='text-sm font-semibold'>{index + 1}</span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>

      {isError ? (
        <Card className='border-destructive/30 bg-destructive/5'>
          <CardHeader>
            <CardTitle>Error loading workers</CardTitle>
            <CardDescription>There was an issue fetching worker data.</CardDescription>
          </CardHeader>
          <CardFooter>
            <Button variant='outline' onClick={() => refetch()}>
              Retry
            </Button>
          </CardFooter>
        </Card>
      ) : null}
    </div>
  );
};
