import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';

import { store } from '../store/app/store';
import { authQuery } from '../store/features/auth/authQuery';
import { setToken } from '@/lib/session';
import { ProcurementRoutes } from './ProcurementRoutes';
import { SuperadminRoutes } from './Superadmin';

/**
 * Every dashboard tree has to carry its own auth guard.
 *
 * The defect (checked.md P1-18): ProcurementRoutes and SuperadminRoutes had none. They
 * were protected only because AppRoutes happened to select them from the user's role,
 * which puts the requirement one file away from the routes that need it — one refactor
 * of the role branching and the platform's businesses, plans, subscriptions and every
 * purchase order and supplier record render for whoever navigated there.
 *
 * Each tree is mounted directly rather than through AppRoutes, so what is under test is
 * the tree's own guard and not the branch selection that used to stand in for it.
 */

const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

/** `meStatus` decides only /users/me. Everything else answers with a shape the layouts
 *  can mount against. */
const stubFetch = (meStatus: 200 | 401) => {
  const json = (body: unknown) =>
    new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } });

  vi.stubGlobal(
    'fetch',
    vi.fn(async (input: RequestInfo | URL) => {
      const url = requestUrl(input);

      if (url.includes('/users/me')) {
        if (meStatus === 401) {
          return new Response(JSON.stringify({ message: 'Unauthenticated.' }), {
            status: 401,
            headers: { 'Content-Type': 'application/json' },
          });
        }

        return json({
          data: {
            id: 1,
            name: 'Test User',
            role: { name: 'siteadmin' },
            business: { id: 1, name: 'Test Business' },
            business_branch_id: 1,
          },
        });
      }

      return json({ data: [] });
    })
  );
};

/** Location has to be observed from outside the tree: ProtectedRoutes renders
 *  <Navigate to="/login"> in place of its children, so the tree itself never renders the
 *  new path and cannot report it. */
const LocationProbe = () => {
  const { pathname } = useLocation();
  return <span data-testid='pathname'>{pathname}</span>;
};

const renderTree = (Tree: React.ComponentType, path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <LocationProbe />
        <Routes>
          <Route path='*' element={<Tree />} />
        </Routes>
      </MemoryRouter>
    </Provider>
  );

const currentPath = () => screen.getByTestId('pathname').textContent;

const TREES = [
  ['ProcurementRoutes', ProcurementRoutes, '/procurement/purchase-orders'],
  ['SuperadminRoutes', SuperadminRoutes, '/businesses'],
] as const;

beforeEach(async () => {
  localStorage.clear();
  setToken('a.valid.token.value');
  store.dispatch(authQuery.util.resetApiState());
});

describe('every dashboard tree guards itself', () => {
  it.each(TREES)('%s redirects to /login when /users/me is rejected', async (_name, Tree, path) => {
    stubFetch(401);

    renderTree(Tree, path);

    // The redirect is the observable difference, not emptiness.
    //
    // A first version of this file asserted the tree rendered nothing, and it passed
    // with the guard deleted: ProcurementRoutes has its own `isLoading` early return, so
    // it is blank on a 401 whether or not it is guarded. Removing both guards left all
    // four tests green. Asserting the redirect is what gives the test teeth — it can
    // only happen if ProtectedRoutes ran.
    await waitFor(
      () => {
        expect(currentPath()).toBe('/login');
      },
      { timeout: 4000 }
    );
  });

  it.each(TREES)('%s stays put for a signed-in user', async (_name, Tree, path) => {
    stubFetch(200);

    const { container } = renderTree(Tree, path);

    // The guard must not have broken the tree it was added to: content appears and the
    // URL survives, rather than the new guard bouncing a legitimate session.
    await waitFor(
      () => {
        expect(container.textContent?.trim()).not.toBe('');
      },
      { timeout: 4000 }
    );

    expect(currentPath()).toBe(path);
  });
});
