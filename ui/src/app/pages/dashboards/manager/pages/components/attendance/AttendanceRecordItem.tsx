import type { ReactNode } from 'react';

export const AttendanceRecordItem = ({ name, status }: { name: string; status: string }) => (
  <div className='rounded-3xl border border-border/70 bg-background p-4'>
    <p className='font-semibold'>{name}</p>
    <p className='text-sm text-muted-foreground'>{status}</p>
  </div>
);
