import { Navigate, Route, Routes } from 'react-router-dom';
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
import { DASHBOARD_PREFIX } from '@/lib/rolePrefix';
import { getToken } from '@/lib/session';

export const AppRoutes = () => {
  const { data, isLoading, error } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const hasToken = Boolean(getToken());
  const onDashboard = window.location.pathname.startsWith(DASHBOARD_PREFIX);
  const onOnboarding = window.location.pathname.startsWith('/onboarding');

  if (hasToken && isLoading) {
    return <PageLoadingState />;
  }

  // A rejected /me means the token is dead. Without this the role stayed
  // undefined, no role branch mounted, and a signed-in user with an expired
  // token was shown the public marketing homepage with the dead token still in
  // localStorage.
  if (error) {
    return <Navigate to='/login' replace />;
  }

  // No token and a dashboard URL: there is nothing to render but a 404, so send
  // them to the login screen instead of a dead end.
  if (!hasToken && onDashboard) {
    return <Navigate to='/login' replace />;
  }

  // A real account with no business yet: the person signed up and never finished
  // creating their business, so they have no role and none of the role trees below
  // match. That used to drop them on the 404 page, from a dashboard URL they had no
  // way to get back out of. RequireBusiness is refusing every tenant route for them
  // with "onboarding_incomplete", so the onboarding step is where they belong.
  const onboardingComplete = data?.onboarding?.complete !== false;
  if (hasToken && !onboardingComplete && !onOnboarding) {
    return <Navigate to='/onboarding' replace />;
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
      {(role === 'CoreSupport' || role === 'siteadmin') && <Route path='dashboard/*' element={<SuperadminRoutes />} />}
      {role === 'Operations' && <Route path='dashboard/*' element={<OperationsRoutes />} />}
      {role === 'Procurement' && <Route path='dashboard/*' element={<ProcurementRoutes />} />}
      {role === 'staff' && <Route path='dashboard/*' element={<StaffDashboard />} />}

      {/* Fallback */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
