import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
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
import { getToken } from '@/lib/session';
import { QueryErrorState } from '@/app/components/QueryErrorState';

/**
 * Every role mounts under a single /dashboard tree (see the routes below), so the URL
 * carries no role. Matching the segment and not just the characters keeps an unrelated
 * URL such as /dashboard-notes from counting as a protected route.
 */
const isDashboardPath = (pathname: string): boolean =>
  pathname === '/dashboard' || pathname.startsWith('/dashboard/');

/**
 * RTK reports a rejected request either as `{ status: <number>, data }` when the
 * server actually answered, or as `{ status: 'FETCH_ERROR' }` when no response
 * ever arrived — API down, wrong host, CORS, offline. Only the first is an HTTP
 * answer, and only 401/403 among those mean the session itself is not valid.
 */
const httpStatusOf = (error: unknown): number | null => {
  const status = (error as { status?: unknown } | undefined)?.status;
  return typeof status === 'number' ? status : null;
};

const isAuthFailure = (error: unknown): boolean => {
  const status = httpStatusOf(error);
  return status === 401 || status === 403;
};

export const AppRoutes = () => {
  const { data, isLoading, error, isFetching, refetch } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const hasToken = Boolean(getToken());

  // From the router, not window.location: this component does not re-render on
  // navigation by itself, so reading the global here left these flags describing
  // wherever the user had been rather than where they were going.
  const { pathname } = useLocation();
  const onDashboard = isDashboardPath(pathname);
  const onLogin = pathname === '/login';

  if (hasToken && isLoading) {
    return <PageLoadingState />;
  }

  // 401/403 from /me means the stored token is dead, so there is no role and nothing
  // below can render.
  //
  // Two conditions, and both are load-bearing. This branch returns a <Navigate>
  // instead of <Routes>, and a <Navigate> renders nothing at all, so firing it while
  // already on /login replaced the login form with a blank page and left the app
  // stuck redirecting to the page it was already on. And `hasToken` matters because
  // authListenerMiddleware clears the token on this same 401: once that has happened
  // the error left in the query cache is stale, and treating it as a live failure
  // bounced the user back to /login from every public page they tried to reach.
  if (hasToken && error && isAuthFailure(error) && !onLogin) {
    return <Navigate to='/login' replace />;
  }

  // Everything else that can fail here is a server problem, not an auth problem: a
  // 500, or no response at all. Redirecting to /login for those made every route —
  // the landing page included — bounce to the login screen, and because the token
  // was left sitting in localStorage the bounce repeated on every navigation. So a
  // non-auth failure only blocks the one thing that genuinely cannot render without
  // a known role, which is the dashboard.
  if (error && onDashboard) {
    return (
      <div className='mx-auto max-w-lg p-6'>
        <QueryErrorState
          title='Unable to reach the server'
          description='We could not load your account. Check your connection and try again.'
          onRetry={refetch}
          retrying={isFetching}
        />
      </div>
    );
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
  //
  // Scoped to dashboard URLs deliberately. Applied to every URL it also swallowed
  // /login, which left a half-onboarded user unable to sign in as somebody else,
  // and swallowed the marketing homepage they were trying to reach.
  const onboardingComplete = data?.onboarding?.complete !== false;
  if (hasToken && !onboardingComplete && onDashboard) {
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
