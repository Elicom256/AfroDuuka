import { lazy, Suspense, type ComponentType } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { getToken } from '@/lib/session';
import { isDashboardPath } from '@/lib/routes';
import { dashboardTreeForRole, normaliseRoleName } from '@/lib/roles';
import { QueryErrorState } from '@/app/components/QueryErrorState';

// Route-level code splitting. Every page and role tree is loaded on demand so a
// visitor to the marketing site never downloads a dashboard, and one role never
// downloads another's screens. Each module is a named export, so the dynamic
// import is unwrapped into the shape React.lazy expects.
const named = <T extends Record<string, unknown>, K extends keyof T>(
  loader: () => Promise<T>,
  key: K,
) => lazy(() => loader().then((m) => ({ default: m[key] as ComponentType<Record<string, unknown>> })));

const HomeLayout = named(() => import('../pages/public/HomeLayout'), 'HomeLayout');
const Home = named(() => import('../pages/public/Home'), 'Home');
const PricingPage = named(() => import('../pages/public/PricingPage'), 'PricingPage');
const About = named(() => import('../pages/public/About'), 'About');
const Documentation = named(() => import('../pages/public/Documentation'), 'Documentation');
const TermsOfService = named(() => import('../pages/public/TermsOfService'), 'TermsOfService');
const PrivacyPolicy = named(() => import('../pages/public/PrivacyPolicy'), 'PrivacyPolicy');
const Login = named(() => import('../pages/public/Login'), 'Login');
const SignUp = named(() => import('../pages/public/SignUp'), 'SignUp');
const Onboarding = named(() => import('../pages/public/Onboarding'), 'Onboarding');
const ExecutiveRoutes = named(() => import('./ExecutiveRoutes'), 'ExecutiveRoutes');
const OperationsRoutes = named(() => import('./OperationsRoutes'), 'OperationsRoutes');
const BranchManagerRoutes = named(() => import('./BranchManagerRoutes'), 'BranchManagerRoutes');
const ProcurementRoutes = named(() => import('./ProcurementRoutes'), 'ProcurementRoutes');
const SuperadminRoutes = named(() => import('./Superadmin'), 'SuperadminRoutes');
const NotFound = named(() => import('./NotFound'), 'NotFound');
const NoDashboardAccess = named(() => import('./NoDashboardAccess'), 'NoDashboardAccess');

/**
 * Which tree a role gets, keyed by *normalised* role name.
 *
 * Comparing raw strings (`role === 'Executive'`) meant a role stored as 'executive'
 * or 'branch_manager' matched nothing at all and silently produced a 404 for a
 * legitimate login. That is the same defect RolePermissions::roleName() already
 * fixed on the api side; see lib/roles.ts.
 */
const ROLE_TREES = {
  executive: ExecutiveRoutes,
  branchmanager: BranchManagerRoutes,
  coresupport: SuperadminRoutes,
  siteadmin: SuperadminRoutes,
  operations: OperationsRoutes,
  procurement: ProcurementRoutes,
} as const;

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
  const hasToken = Boolean(getToken());

  // Never ask who you are when there is nothing to ask with. Without this, every
  // public page fired /users/me on mount, which made the marketing site depend on the
  // auth endpoint: a stale token turned /pricing into a redirect to /login, and a
  // slow or unreachable api left the landing page waiting on a loader.
  // authBaseQuery already answers "no session" locally, so skipping is strictly less
  // work and cannot fail.
  const { data, isLoading, error, isFetching, refetch } = useLoggedinUserQuery({
    skip: !hasToken,
  });

  const role = data?.data?.role?.name;
  const treeKey = dashboardTreeForRole(role);
  const RoleTree = treeKey ? ROLE_TREES[treeKey] : null;

  // From the router, not window.location: this component does not re-render on
  // navigation by itself, so reading the global here left these flags describing
  // wherever the user had been rather than where they were going.
  const { pathname } = useLocation();
  const onDashboard = isDashboardPath(pathname);

  // Only the dashboard cannot be drawn without a known role. Blocking a public page
  // on this query is what turned an unreachable api into a blank homepage; the navbar
  // renders a correct signed-out state without it.
  if (hasToken && isLoading && onDashboard) {
    return <PageLoadingState />;
  }

  // 401/403 from /me means the stored token is dead.
  //
  // Only a protected page may act on that. This branch returns a <Navigate> *instead
  // of* <Routes>, so it replaces the entire page, and it used to exempt nothing but
  // /login itself. A token survives logout (UserProfile.handleLogout never cleared
  // it) and nothing else was clearing it either (authListenerMiddleware was written
  // but never registered in the store), so a stale token made /, /pricing, /about,
  // /terms and /privacy permanently unreachable — reproduced as a redirect to /login
  // on every load, with no way back.
  //
  // authListener now clears the token, and this branch is scoped to the dashboard so
  // that a stale token degrades to signed-out chrome instead of ejecting the visitor.
  if (hasToken && error && isAuthFailure(error) && onDashboard) {
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
    <Suspense fallback={<PageLoadingState />}>
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
        {RoleTree && <Route path='dashboard/*' element={<RoleTree />} />}

        {/* Signed in, but holding a role this build has no dashboard for — the seeded
            `editor`, `supplier` and `customer` roles all land here. Without this they
            fell through to the marketing 404 below, which is a dead end reached by a
            perfectly legitimate login. Scoped to dashboard URLs, because an unknown
            public URL is still a genuine 404. */}
        {hasToken && !RoleTree && (
          <Route
            path='dashboard/*'
            element={<NoDashboardAccess role={normaliseRoleName(role) || null} />}
          />
        )}

        {/* Fallback */}
        <Route path='*' element={<NotFound />} />
      </Routes>
    </Suspense>
  );
};
