import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Trash2, Ban } from 'lucide-react';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

type Props = {
  onSuspend: () => void;
  onDelete: () => void;
};

export const WorkerActions: React.FC<Props> = ({ onSuspend, onDelete }) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Actions</CardTitle>
      </CardHeader>
      <CardContent className='flex flex-col gap-3'>
        <Button variant='outline' className='justify-start' onClick={onSuspend}>
          <Ban className='mr-2 h-4 w-4' />
          Suspend Worker
        </Button>
        <ConfirmDeleteButton
          onConfirm={onDelete}
          title='Delete this worker?'
          description='Their account and record are removed. This cannot be undone.'
          confirmLabel='Delete worker'
          trigger={
            <Button variant='destructive' className='justify-start'>
              <Trash2 className='mr-2 h-4 w-4' />
              Delete Worker
            </Button>
          }
        />
      </CardContent>
    </Card>
  );
};
