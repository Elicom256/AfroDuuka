import { Route, Routes } from 'react-router-dom';
import { HomeLayout } from '../pages/public/HomeLayout';
import { Home } from '../pages/public/Home';
import { PricingPage } from '../pages/public/PricingPage';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';
import { Login } from '../pages/public/Login';
import { SignUp } from '../pages/public/SignUp';
import { ExecutiveRoutes } from './ExecutiveRoutes';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { OperationsRoutes } from './OperationsRoutes';
import { StaffDashboard } from './StaffDashboard';
import { NotFound } from './NotFound';
import { SuperadminRoutes } from './Superadmin';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { BranchManagerRoutes } from './BranchManagerRoutes';

export const AppRoutes = () => {
  const { data, isLoading, error } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  console.log('user error==>', error);
  if (isLoading) {
    return <PageLoadingState />;
  }

  return (
    <Routes>
      {/* Public routes */}
      <Route element={<HomeLayout />}>
        <Route index element={<Home />} />
        <Route path='pricing' element={<PricingPage />} />
      </Route>
      <Route path='login' element={<Login />} />
      <Route path='signup' element={<SignUp />} />
      <Route path='about' element={<About />} />
      <Route path='documentation' element={<Documentation />} />

      {/* Role-based protected routes */}
      {role === 'Executive' && <Route path='/*' element={<ExecutiveRoutes />} />}
      {role === 'BranchManager' && <Route path='/*' element={<BranchManagerRoutes />} />}
      {role === 'CoreSupport' && <Route path='/*' element={<SuperadminRoutes />} />}
      {role === 'Operations' && <Route path='/*' element={<OperationsRoutes />} />}
      {role === 'Procurement' && <Route path='/*' element={<OperationsRoutes />} />}
      {role === 'staff' && <Route path='/*' element={<StaffDashboard />} />}

      {/* Fallback */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
