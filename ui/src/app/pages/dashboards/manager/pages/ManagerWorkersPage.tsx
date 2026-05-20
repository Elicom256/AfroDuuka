import { TrendingUp, Users, FileText } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchWorkersQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerWorkersPage = () => {
  const { data, isLoading } = useBranchWorkersQuery();
  const workers = resolveList(data, 'workers');
  const activeCount = workers.filter((worker: any) => worker.status === 'active' || worker.is_active).length;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Workers' description='Manage your branch team with worker counts and roles.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Total workers' value={workers.length} icon={<Users className='h-5 w-5' />} />
          <SectionCard title='Active workers' value={activeCount} icon={<TrendingUp className='h-5 w-5' />} />
          <SectionCard
            title='Pending approvals'
            value={workers.filter((worker: any) => worker.status === 'pending').length}
            icon={<FileText className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {workers.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No workers found for this branch yet.</p>
          ) : (
            workers.slice(0, 6).map((worker: any) => (
              <div
                key={worker.id ?? worker.userId ?? worker.email}
                className='rounded-3xl border border-border/70 bg-background p-4'
              >
                <p className='font-semibold'>{worker.username ?? worker.name ?? 'Unnamed worker'}</p>
                <p className='text-sm text-muted-foreground'>
                  {worker.role?.name ?? worker.role ?? 'No role assigned'}
                </p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
