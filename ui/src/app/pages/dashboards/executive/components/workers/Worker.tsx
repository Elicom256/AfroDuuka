import React from 'react';
import { useParams } from 'react-router-dom';
import { useDeleteBranchWorkerMutation, useUpdateBranchWorkerMutation } from '@/app/store/features/branch';

import { useGetWorkerInfoQuery } from '@/app/store/features/business/workers/workersQuery';

import { toast } from 'sonner';
import { WorkerHeader } from './WorkerHeader';
import { WorkerBasicInfo } from './WorkerBasicInfo';
import { AttendanceHistory } from './AttendanceHistory';
import { WorkerActions } from './WorkerActions';
import { PageLoadingState } from '@/utils/PageLoadingState';

// Main Component
export const Worker: React.FC = () => {
  const { id } = useParams<{ id: string }>();

  const { data, isLoading } = useGetWorkerInfoQuery(id!, { skip: !id });

  const [suspend, { isLoading: isUpdating }] = useUpdateBranchWorkerMutation();
  const [destroy, { isLoading: isDeleting }] = useDeleteBranchWorkerMutation();

  const worker = data?.worker?.user;
  const employee = data?.worker;
  const attendances = employee?.attendances || [];

  console.log('Worker data==>', data);

  const handleSuspendWorker = async () => {
    if (!worker) return;
    const user = { ...worker, status: 'suspended' };
    const res = await suspend({ body: user, id: worker.id }).unwrap();
    toast.success(res.message || 'Worker Suspended');
  };

  const handleDeleteWorker = async () => {
    if (!worker) return;
    const res = await destroy(worker.id).unwrap();
    toast.success(res.message || 'Worker Deleted');
  };

  if (isLoading || isUpdating || isDeleting) {
    return (
      <div className='flex items-center justify-center py-20'>
        <PageLoadingState />
      </div>
    );
  }

  if (!worker) {
    return (
      <div className='flex items-center justify-center py-20'>
        <p className='text-muted-foreground'>Worker not found</p>
      </div>
    );
  }

  return (
    <div className='p-4 md:p-6 space-y-6'>
      <WorkerHeader worker={worker} />

      <div className='grid gap-6 lg:grid-cols-3'>
        {/* Left Column - Info */}
        <div className='lg:col-span-2 space-y-6'>
          <WorkerBasicInfo worker={worker} employee={employee} />

          <AttendanceHistory
            attendances={attendances}
            attendanceHistory={data?.attendance_history}
            workerId={worker.id}
          />
        </div>

        {/* Right Column - Quick Actions */}
        <div className='space-y-6'>
          <WorkerActions onSuspend={handleSuspendWorker} onDelete={handleDeleteWorker} />
        </div>
      </div>
    </div>
  );
};
