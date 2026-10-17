import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { QueryErrorState } from '@/app/components/QueryErrorState';

const isTransportFailure = (error: unknown): boolean => {
  const status = (error as { status?: unknown } | undefined)?.status;
  return status === 'FETCH_ERROR' || status === 'TIMEOUT_ERROR';
};

export const ProtectedRoutes: React.FC = () => {
  const { data, isLoading, error } = useLoggedinUserQuery();
  const user = data?.data;
  if (isLoading) {
    return <PageLoadingState />;
  }
  // A missing response is not a signed-out session. Sending that to /login is
  // what made a refresh look like a logout when the API answer never reached
  // the page. Only the absence of a user with no transport failure is a login.
  if (!user && isTransportFailure(error)) {
    return (
      <div className='mx-auto max-w-lg p-6'>
        <QueryErrorState
          title='Unable to reach the server'
          description='Your session is still on this device. Check the connection and reload.'
        />
      </div>
    );
  }
  if (!user) {
    return <Navigate to='/login' replace />;
  }

  // Otherwise render the child route
  return <Outlet />;
};
