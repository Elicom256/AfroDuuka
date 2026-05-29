import { useState } from 'react';
import {
  useRegisterWorkerMutation,
  useUpdateWorkerMutation,
  useDeleteWorkerMutation,
  useGetWorkersInfoQuery,
} from '@/app/store/features/business/workers/workersQuery';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { Plus } from 'lucide-react';
import { WorkersTable, type WorkerItem } from '../components/workers-table';
import { WorkerFormDialog } from '../components/worker-form-dialog';
import { useRolesQuery } from '@/app/store/features/business/roles/rolesQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
// import { useBranchWorkersQuery } from '@/app/store/features/branch';

export const AdminWorkersPage = () => {
  const { data, isLoading, isError, refetch } = useGetWorkersInfoQuery();
  // const { data: test } = useBranchWorkersQuery();
  const { data: sections } = useBranchesQuery();
  const [deleteWorker, { isLoading: isDeleting }] = useDeleteWorkerMutation();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedWorker, setSelectedWorker] = useState<WorkerItem | null>(null);
  const { data: roles } = useRolesQuery();
  const branches = sections?.branches;

  const openNewWorker = () => {
    setSelectedWorker(null);
    setDialogOpen(true);
  };
  // console.log('worker here==>', test);

  const openEditWorker = (worker: WorkerItem) => {
    setSelectedWorker(worker);
    setDialogOpen(true);
  };
  const workers = data?.workers;
  console.log('workers==>', workers);

  const handleDelete = async (worker: WorkerItem) => {
    const confirmed = window.confirm(`Delete ${worker.name ?? 'this worker'}?`);
    if (!confirmed) return;

    try {
      await deleteWorker({ id: worker.id, userData: { isActive: false } }).unwrap();
      toast.success('Worker deleted successfully.');
    } catch (error) {
      toast.error('Unable to delete worker.');
    }
  };

  return (
    <div className='space-y-6'>
      <div className='flex flex-col gap-4 rounded-3xl border border-border/70 bg-card p-6 sm:flex-row sm:items-center sm:justify-between'>
        <div>
          <p className='text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground'>Workers</p>
          <h2 className='mt-2 text-2xl font-semibold'>Team management</h2>
          <p className='mt-2 max-w-2xl text-sm text-muted-foreground'>
            Manage worker accounts and keep your team data up to date.
          </p>
        </div>
        <Button onClick={openNewWorker} size='sm'>
          <Plus className='mr-2 h-4 w-4' />
          Add worker
        </Button>
      </div>

      {isError ? (
        <Card className='border-destructive/30 bg-destructive/5'>
          <CardHeader>
            <CardTitle>Unable to load workers</CardTitle>
            <CardDescription>There was a problem fetching data from the server.</CardDescription>
          </CardHeader>
          <CardFooter>
            <Button variant='outline' onClick={() => refetch()}>
              Retry
            </Button>
          </CardFooter>
        </Card>
      ) : (
        <WorkersTable
          workers={workers}
          isLoading={isLoading}
          onEdit={openEditWorker}
          onDelete={handleDelete}
          isDeleting={isDeleting}
        />
      )}

      <WorkerFormDialog
        open={dialogOpen}
        onOpenChange={(open: boolean) => {
          setDialogOpen(open);
          if (!open) setSelectedWorker(null);
        }}
        roles={roles}
        branches={branches}
        selectedWorker={selectedWorker}
        setDialogOpen={setDialogOpen}
      />
    </div>
  );
};
