import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const ProtectedRoutes: React.FC = () => {
  const { data, isLoading } = useLoggedinUserQuery();
  const user = data?.data;
  console.log('token of tokens==>', data);
  if (isLoading) {
    return <PageLoadingState />;
  }
  // If no token, redirect to login
  if (!user) {
    return <Navigate to='/login' replace />;
  }

  // Otherwise render the child route
  return <Outlet />;
};
