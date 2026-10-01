import { Route, Routes } from 'react-router-dom';
import { HomeLayout } from '../pages/public/HomeLayout';
import { Home } from '../pages/public/Home';
import { PricingPage } from '../pages/public/PricingPage';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';
import { TermsOfService } from '../pages/public/TermsOfService';
import { PrivacyPolicy } from '../pages/public/PrivacyPolicy';
import { Login } from '../pages/public/Login';
import { SignUp } from '../pages/public/SignUp';
import { Onboarding } from '../pages/public/Onboarding';
import { ExecutiveRoutes } from './ExecutiveRoutes';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { OperationsRoutes } from './OperationsRoutes';
import { StaffDashboard } from './StaffDashboard';
import { NotFound } from './NotFound';
import { SuperadminRoutes } from './Superadmin';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { BranchManagerRoutes } from './BranchManagerRoutes';
import { ProcurementRoutes } from './ProcurementRoutes';

export const AppRoutes = () => {
  const { data, isLoading, error } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
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
      <Route path='onboarding' element={<Onboarding />} />
        <Route path='about' element={<About />} />
        <Route path='documentation' element={<Documentation />} />
        <Route path='terms' element={<TermsOfService />} />
        <Route path='privacy' element={<PrivacyPolicy />} />

      {/* Role-based protected routes, all mounted at /dashboard/* so that the
          hardcoded '/dashboard/...' links throughout the app resolve. The tree
          is chosen from the role, so the URL does not need to repeat it. */}
      {role === 'Executive' && <Route path='dashboard/*' element={<ExecutiveRoutes />} />}
      {role === 'BranchManager' && <Route path='dashboard/*' element={<BranchManagerRoutes />} />}
      {role === 'CoreSupport' && <Route path='dashboard/*' element={<SuperadminRoutes />} />}
      {role === 'Operations' && <Route path='dashboard/*' element={<OperationsRoutes />} />}
      {role === 'Procurement' && <Route path='dashboard/*' element={<ProcurementRoutes />} />}
      {role === 'staff' && <Route path='dashboard/*' element={<StaffDashboard />} />}

      {/* Fallback */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
