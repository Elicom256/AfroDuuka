import { TrendingUp, Users, FileText } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchWorkersQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useNavigate } from 'react-router-dom';

export const ManagerWorkersPage = () => {
  const navigate = useNavigate();
  const { data, isLoading } = useBranchWorkersQuery();
  const workers = data?.data;
  console.log('workers==>', workers);

  const activeCount = workers?.filter((worker: any) => worker.status === 'active' || worker.is_active).length;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Workers' description='Manage your branch team with worker counts and roles.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Total workers' value={workers.length} icon={<Users className='h-5 w-5' />} />
          <SectionCard title='Active workers' value={activeCount} icon={<TrendingUp className='h-5 w-5' />} />
          <SectionCard
            title='Pending approvals'
            value={workers && workers.filter((worker: any) => worker.status === 'pending').length}
            icon={<FileText className='h-5 w-5' />}
          />
        </div>
        <hr />
        <div className='grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-4'>
          {workers.map((worker: any) => (
            <Card
              key={worker.id}
              className='hover:shadow-md w-full h-full hover:scale-102 transition-all ease-in-out cursor-pointer'
              onClick={() => navigate(`/manager/workers/${worker.id}`)}
            >
              <CardHeader>
                <CardAction className='rounded-full bg-white/20 px-2'> {worker.status}</CardAction>
                <CardTitle className='text-lg'>{worker.username ?? worker.name ?? 'Unnamed worker'}</CardTitle>
                <CardDescription>
                  <p>{worker.email}</p>
                  <p>{worker.role?.name ?? worker.role ?? 'No role assigned'}</p>
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className='flex gap-2'>{/* <EditProductCategory category={category} /> */}</div>
              </CardContent>
            </Card>
          ))}
        </div>
        {workers.length === 0 && (
          <div className='text-center py-8'>
            <p className='text-muted-foreground'>No found for this branch.</p>
          </div>
        )}
      </ManagerPageShell>
    </div>
  );
};
