import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Mail, Phone, ShieldCheck, Fingerprint, Calendar } from 'lucide-react';

type Props = {
  worker: any;
  employee: any;
};

export const WorkerBasicInfo: React.FC<Props> = ({ worker, employee }) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Basic Information</CardTitle>
      </CardHeader>
      <CardContent className='space-y-6'>
        <div className='grid gap-4 md:grid-cols-2'>
          <InfoItem icon={<Mail className='h-4 w-4' />} label='Email' value={worker.email} />
          <InfoItem icon={<Phone className='h-4 w-4' />} label='Phone' value={worker.phone} />
          <InfoItem icon={<ShieldCheck className='h-4 w-4' />} label='Role' value={worker.role?.name} />
          <InfoItem icon={<Fingerprint className='h-4 w-4' />} label='NIN' value={worker.nin || 'N/A'} />
          <InfoItem icon={<Calendar className='h-4 w-4' />} label='Hire Date' value={employee?.hire_date} />
          <InfoItem icon={<Calendar className='h-4 w-4' />} label='Department' value={employee?.department} />
        </div>
      </CardContent>
    </Card>
  );
};

const InfoItem = ({ icon, label, value }: { icon: React.ReactNode; label: string; value: React.ReactNode }) => (
  <div className='flex gap-3 rounded-xl border bg-muted/30 p-4'>
    <div className='mt-0.5 text-muted-foreground'>{icon}</div>
    <div>
      <p className='text-sm text-muted-foreground'>{label}</p>
      <p className='font-medium'>{value || 'N/A'}</p>
    </div>
  </div>
);
