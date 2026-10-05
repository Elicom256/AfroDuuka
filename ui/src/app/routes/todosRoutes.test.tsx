import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { AppRoutes } from './AppRoutes';
import { store } from '../store/app/store';
import { authQuery } from '../store/features/auth/authQuery';
import { setToken } from '@/lib/session';
import { ExecutiveSidebar } from '../pages/dashboards/executive/ExecutiveSidebar';
import { BranchManagerSidebar } from '../pages/dashboards/branch-manager/BranchManagerSidebar';
import { OperationsSidebar } from '../pages/dashboards/Operations/OperationsSidebar';
import { ProcurementSidebar } from '../pages/dashboards/procurement/ProcurementSidebar';
import { SuperadminSidebar } from '../pages/dashboards/superadmin/SuperadminSidebar';
import { StaffSidebar } from '../pages/dashboards/staff/StaffSidebar';

/**
 * Regression tests for /dashboard/todos returning 404.
 *
 * The bug: `path='todos'` was declared in ExecutiveRoutes only. Every dashboard tree
 * ends with its own <Route path='*' element={<NotFound/>} />, and AppRoutes picks the
 * tree from the signed-in user's role, so a BranchManager asking for /dashboard/todos
 * matched no route and got the 404 page — even though BranchManagerSidebar has
 * advertised "Tasks -> Todos" since the feature was added, and the api authorises
 * todos for any signed-in user (auth:sanctum, no role middleware).
 *
 * The fix declares the route in each tree, so these tests pin the route existing in the
 * tree the user's role actually selects. Asserting on the role name rather than on
 * "todos" existing somewhere matters: a test that rendered ExecutiveRoutes would have
 * passed while the reported bug was still live.
 */

/** fetchBaseQuery passes `fetch` a Request, so the URL has to be read off `.url`.
 *  String(request) is "[object Request]", which silently turns every match below into
 *  a miss and would make /users/me fall through to a success. */
const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

type MeResponse = {
  id: number;
  name: string;
  role: { name: string };
  // The sidebars render their nav only when `userData.data.business` is present
  // (ExecutiveSidebar.tsx:167 and the same check in the other layouts), and fall back
  // to a branch with no links otherwise. Without this the nav assertions below would
  // pass a stubbed page with no sidebar at all.
  business: { id: number; name: string };
  business_branch_id: number;
};

/**
 * Stubs /users/me with a role, plus a shape for everything else the layout and the page
 * ask for on mount. A layout that queries a missing endpoint renders a loading or empty
 * shell, and the page under test would never mount — the test would then fail for a
 * reason that has nothing to do with routing.
 */
const stubFetch = (roleName: string) => {
  const me: MeResponse = {
    id: 1,
    name: 'Test User',
    role: { name: roleName },
    business: { id: 1, name: 'Test Business' },
    business_branch_id: 1,
  };

  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = requestUrl(input);
    const json = (body: unknown) =>
      new Response(JSON.stringify(body), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });

    if (url.includes('/users/me')) return json({ data: me });

    // The todos page itself. An empty list still renders the page heading.
    if (url.includes('/users/todos')) return json({ data: [] });
    if (url.includes('/users/notifications')) return json({ data: [], unread_count: 0 });
    if (url.includes('/business-branches')) return json({ data: [] });

    return json({ data: [] });
  });

  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
};

type SessionSlice = { queries?: Record<string, { endpointName?: string; status?: string }> };

/**
 * Waits for /users/me to settle before asserting on the sidebar.
 *
 * Every sidebar renders `to={!role ? '/login' : itemPath}`, so before the session
 * resolves the Todos link points at /login. Asserting then would read the pre-session
 * render and pass or fail on timing rather than on the href. ProcurementSidebar lost
 * exactly this race before the wait was added.
 *
 * Located by endpointName rather than by serialised cache key, so it does not depend on
 * how the query was keyed.
 */
const waitForSession = async () => {
  await waitFor(() => {
    const slice = (store.getState() as Record<string, SessionSlice>)[authQuery.reducerPath];
    const entry = Object.values(slice?.queries ?? {}).find((q) => q?.endpointName === 'loggedinUser');

    expect(entry?.status).toBe('fulfilled');
  });
};

const renderAt = (path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <AppRoutes />
      </MemoryRouter>
    </Provider>
  );

beforeEach(async () => {
  localStorage.clear();
  setToken('a.valid.token.value');
  store.dispatch(authQuery.util.resetApiState());
});

/** The todos page and the 404 page are told apart by what they actually render, not
 *  by the URL: a wrong tree and a missing route both leave the path untouched. */
const showsTodosPage = () => screen.queryAllByText('Quick task entry').length > 0;
const showsNotFound = () => screen.queryAllByText('Page Not Found').length > 0;

describe('/dashboard/todos is reachable from every dashboard', () => {
  it.each([
    ['Executive', 'executive'],
    ['BranchManager', 'branchmanager'],
    ['Operations', 'operations'],
    ['Procurement', 'procurement'],
    ['siteadmin', 'siteadmin'],
    ['CoreSupport', 'coresupport'],
  ])('renders the todos page for %s rather than the 404 page', async (roleName) => {
    stubFetch(roleName);

    renderAt('/dashboard/todos');

    await waitFor(() => {
      expect(screen.queryAllByText('Quick task entry').length + screen.queryAllByText('Page Not Found').length)
        .toBeGreaterThan(0);
    });

    expect(showsTodosPage()).toBe(true);
    expect(showsNotFound()).toBe(false);
  });

  it('serves the standalone create-todo route as well', async () => {
    stubFetch('BranchManager');

    renderAt('/dashboard/create-todo');

    await waitFor(() => {
      expect(screen.queryAllByText('Page Not Found').length).toBe(0);
    });

    expect(showsNotFound()).toBe(false);
  });

  it('still sends an unknown dashboard path to the 404 page', async () => {
    // Guards the catch-all. Declaring a route must not have replaced it, and a test
    // suite that only ever checks the happy path cannot tell those apart.
    stubFetch('BranchManager');

    renderAt('/dashboard/definitely-not-a-page');

    await waitFor(() => {
      expect(screen.queryAllByText('Page Not Found').length).toBeGreaterThan(0);
    });
  });

  it('never asks for todos with a role that has no dashboard', async () => {
    // A role outside ROLE_DASHBOARD_TREE must still be refused, so adding the route
    // did not quietly widen who can reach a per-user page.
    stubFetch('supplier');

    renderAt('/dashboard/todos');

    await waitFor(() => {
      expect(screen.queryAllByText('Page Not Found').length + screen.queryAllByText(/no dashboard/i).length)
        .toBeGreaterThan(0);
    });

    expect(showsTodosPage()).toBe(false);
  });
});

/**
 * The route existing is only half the fix. The reason this bug survived is that
 * BranchManagerSidebar has advertised "Tasks -> Todos" since the feature was added, so
 * the app was actively sending people into a 404. Every sidebar that renders a Todos
 * link must therefore have a route to resolve it, and the link must point at the path
 * that route is registered on.
 *
 * These render the sidebar component on its own rather than the whole dashboard at
 * /dashboard. Going through the dashboard made the assertions depend on every widget's
 * data shape — RecentSales threw "sales is not iterable" against a generic stub, which
 * says nothing about navigation. The sidebar only needs the session and the feature
 * settings, both of which the stub above answers.
 */
describe('every Todos nav link resolves to the todos route', () => {
  const sidebars = [
    ['ExecutiveSidebar', ExecutiveSidebar],
    ['BranchManagerSidebar', BranchManagerSidebar],
    ['OperationsSidebar', OperationsSidebar],
    ['ProcurementSidebar', ProcurementSidebar],
    ['SuperadminSidebar', SuperadminSidebar],
    ['StaffSidebar', StaffSidebar],
  ] as const;

  it.each(sidebars)('%s links Todos at /dashboard/todos', async (_name, Sidebar) => {
    stubFetch('Executive');

    render(
      <Provider store={store}>
        <MemoryRouter>
          <Sidebar />
        </MemoryRouter>
      </Provider>
    );

    await waitForSession();
    await waitFor(() => {
      expect(screen.queryAllByText('Todos').length).toBeGreaterThan(0);
    });

    const hrefs = screen.getAllByText('Todos').map((el) => el.closest('a')?.getAttribute('href'));

    expect(hrefs.length).toBeGreaterThan(0);
    // Every rendered copy must agree: the mobile Sheet and the desktop aside both
    // render the sidebar, and a mismatch would mean one of them 404s.
    expect(new Set(hrefs)).toEqual(new Set(['/dashboard/todos']));
  });
});
