import React from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { User } from 'lucide-react';
import { Card } from '@/components/ui/card';

type Props = {
  worker: any;
};

export const WorkerHeader: React.FC<Props> = ({ worker }) => {
  const initials = worker.firstname
    ?.split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase();

  return (
    <Card className='overflow-hidden border shadow-sm'>
      <div className='bg-gradient-to-r from-primary/10 via-primary/5 to-background p-6'>
        <div className='flex flex-col gap-5 md:flex-row md:items-center md:justify-between'>
          <div className='flex items-center gap-4'>
            <Avatar className='h-20 w-20 border-2 border-white shadow'>
              <AvatarFallback className='text-2xl font-bold bg-primary/10 text-primary'>{initials}</AvatarFallback>
            </Avatar>

            <div>
              <h1 className='text-3xl font-bold tracking-tight'>
                {worker.firstname} {worker.lastname}
              </h1>
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

          <div className='text-right'>
            <div className='text-sm text-muted-foreground'>@{worker.username?.replace('@', '')}</div>
            <div className='text-xs text-muted-foreground mt-1'>
              Employee Code: <span className='font-mono'>{worker.employee_code}</span>
            </div>
          </div>
        </div>
      </div>
    </Card>
  );
};
