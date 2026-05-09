import { Spinner } from '@/components/ui/spinner';
import React from 'react';

export const PageLoadingState:React.FC = () => {
  return (
    <div className='flex items-center justify-center py-24'>
      <Spinner className='size-24' />
    </div>
  );
};
