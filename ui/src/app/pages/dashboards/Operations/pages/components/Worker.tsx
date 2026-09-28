import React from 'react';
import { useParams } from 'react-router-dom';
import {
  useBranchWorkerQuery,
  useDeleteBranchWorkerMutation,
  useUpdateBranchWorkerMutation,
} from '@/app/store/features/branch';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

import { User, Phone, Mail, ShieldCheck, Fingerprint } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const Worker: React.FC = () => {
  const { id } = useParams();

  const { data } = useBranchWorkerQuery(id!, {
    skip: !id,
  });
  const worker = data?.worker;
  const [suspend, { isLoading: isUpdating }] = useUpdateBranchWorkerMutation();
  const [destroy, { isLoading: isDeleting }] = useDeleteBranchWorkerMutation();
  // suspend
  const handleSuspendWorker = async () => {
    const user = { ...worker, status: 'suspended' };
    const res = await suspend({ body: user, id: worker.id }).unwrap();
    toast.success(res.message || 'Worker Suspended');
    console.log('Response==>', res);
    return;
  };

  // delete
  const handleDeleteWorker = async () => {
    const res = await destroy(worker.id).unwrap();
    toast.success(res.message || 'Worker Deleted');
    console.log('Response==>', res);
    return;
  };

  console.log('worker==>', worker);
  if (!id) return null;

  if (isUpdating || isDeleting) {
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

  const initials = worker.name
    ?.split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase();

  return (
    <div className='p-4 md:p-6'>
      <Card className='overflow-hidden border shadow-sm'>
        {/* HEADER */}
        <div className='bg-linear-to-r from-primary/10 via-primary/5 to-background p-6'>
          <div className='flex flex-col gap-5 md:flex-row md:items-center md:justify-between'>
            <div className='flex items-center gap-4'>
              <Avatar className='h-20 w-20 border'>
                <AvatarFallback className='text-xl font-bold'>{initials}</AvatarFallback>
              </Avatar>

              <div>
                <h2 className='text-2xl font-bold tracking-tight'>{worker.name}</h2>

                <div className='mt-2 flex flex-wrap items-center gap-2'>
                  <Badge variant='secondary' className='gap-1'>
                    <User className='h-3.5 w-3.5' />
                    Worker
                  </Badge>

                  <Badge variant={worker.status === 'active' ? 'default' : 'destructive'} className='capitalize'>
                    {worker.status}
                  </Badge>
                </div>
              </div>
            </div>

            <div className='text-sm text-muted-foreground'>@{worker.username?.replace('@', '')}</div>
          </div>
        </div>

        <CardHeader>
          <CardTitle>Worker Information</CardTitle>
          <CardDescription>Full profile and account details</CardDescription>
        </CardHeader>

        <CardContent className='space-y-6'>
          {/* BASIC INFO */}
          <div className='grid gap-4 md:grid-cols-2'>
            <InfoItem icon={<Mail className='h-4 w-4' />} label='Email' value={worker.email || 'N/A'} />

            <InfoItem icon={<Phone className='h-4 w-4' />} label='Phone' value={worker.phone || 'N/A'} />

            <InfoItem icon={<ShieldCheck className='h-4 w-4' />} label='Role' value={worker.role.name} />
            <InfoItem icon={<Fingerprint className='h-4 w-4' />} label='NIN' value={worker.nin || 'Not provided'} />
          </div>

          <Separator />

          {/* ACCOUNT META */}
          {/* <div>
            <h3 className='mb-4 flex items-center gap-2 text-sm font-semibold text-muted-foreground'>
              <BadgeCheck className='h-4 w-4' />
              Account Metadata
            </h3>

            <div className='grid gap-4 md:grid-cols-2'>
              <InfoItem
                icon={<CalendarDays className='h-4 w-4' />}
                label='Created At'
                value={new Date(worker.created_at).toLocaleString()}
              />

              <InfoItem
                icon={<CalendarDays className='h-4 w-4' />}
                label='Updated At'
                value={new Date(worker.updated_at).toLocaleString()}
              />
            </div>
          </div> */}

          {/* BRANCH POWERS */}
          <div className='flex items-center justify-end gap-2'>
            {/* <h3 className='mb-3 text-sm font-semibold text-muted-foreground'>Branch Powers</h3> */}

            {/* <Badge variant='outline' className='capitalize'>
              {worker.branch_powers || 'none'}
            </Badge> */}
            <input
              onClick={handleSuspendWorker}
              className='bg-gray-500 p-2 rounded cursor-pointer'
              type='button'
              value='Suspend'
            />
            <input
              onClick={handleDeleteWorker}
              className='bg-red-500 p-2 rounded cursor-pointer'
              type='button'
              value='Delete'
            />
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

type InfoItemProps = {
  icon: React.ReactNode;
  label: string;
  value: React.ReactNode;
};

const InfoItem: React.FC<InfoItemProps> = ({ icon, label, value }) => {
  return (
    <div className='rounded-xl border bg-muted/30 p-4'>
      <div className='mb-2 flex items-center gap-2 text-sm text-muted-foreground'>
        {icon}
        {label}
      </div>

      <div className='text-sm font-medium break-all'>{value}</div>
    </div>
  );
};
