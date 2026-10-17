import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { AppRoutes } from './AppRoutes';
import { store } from '../store/app/store';
import { authQuery } from '../store/features/auth/authQuery';
import { setToken, clearToken } from '@/lib/session';
import {
  saveSessionProfile,
  loadSessionProfile,
  clearSessionProfile,
} from '@/lib/offline/sessionProfile';

/**
 * Offline cold start.
 *
 * The session query is the single point every dashboard route depends on: without a
 * role AppRoutes cannot pick a tree, and without a user ProtectedRoutes redirects to
 * /login. When the API is unreachable that used to strand the whole dashboard on an
 * "Unable to reach the server" card — reported as a blank sales page — even though the
 * device had a perfectly good token and had seen the profile a moment earlier.
 *
 * These pin the two halves of the fix: a transport failure falls back to the cached
 * profile, and the absence of one still surfaces the honest error rather than a
 * signed-in shell built on nothing. The fallback itself is asserted against the real
 * base query through the store, so it does not depend on mounting the (lazy) role tree.
 */

const me = {
  id: 1,
  name: 'Offline User',
  role: { name: 'Executive' },
  business: { id: 1, name: 'Test Business' },
  business_branch_id: 1,
};

const offlineFetch = () =>
  vi.stubGlobal('fetch', vi.fn(() => Promise.reject(new TypeError('Failed to fetch'))));

const renderAt = (path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <AppRoutes />
      </MemoryRouter>
    </Provider>
  );

/** Run the session query the way AppRoutes does and settle it. */
const fetchSession = () => store.dispatch(authQuery.endpoints.loggedinUser.initiate()).unwrap();

beforeEach(() => {
  localStorage.clear();
  store.dispatch(authQuery.util.resetApiState());
});

afterEach(() => {
  vi.unstubAllGlobals();
  clearSessionProfile();
});

describe('offline session fallback', () => {
  it('serves the cached profile when /users/me cannot be reached', async () => {
    setToken('a.valid.token.value');
    saveSessionProfile({ data: me });
    offlineFetch();

    await expect(fetchSession()).resolves.toEqual({ data: me });
  });

  it('reports the transport error when no profile was ever cached', async () => {
    setToken('a.valid.token.value');
    clearSessionProfile();
    offlineFetch();

    await expect(fetchSession()).rejects.toMatchObject({ status: 'FETCH_ERROR' });
  });

  it('shows the unreachable-server card on the dashboard when there is no cached session', async () => {
    setToken('a.valid.token.value');
    clearSessionProfile();
    offlineFetch();

    renderAt('/dashboard/todos');

    await waitFor(() => {
      expect(screen.queryAllByText('Unable to reach the server').length).toBeGreaterThan(0);
    });

    expect(screen.queryAllByText('Quick task entry').length).toBe(0);
  });
});

describe('session profile storage', () => {
  it('round-trips and clears the cached profile', () => {
    saveSessionProfile({ data: me });
    expect(loadSessionProfile()).toEqual({ data: me });

    clearSessionProfile();
    expect(loadSessionProfile()).toBeNull();
  });

  it('is dropped together with the token on logout', () => {
    saveSessionProfile({ data: me });
    clearToken();
    expect(loadSessionProfile()).toBeNull();
  });

  it('ignores a corrupt cached entry instead of throwing', () => {
    localStorage.setItem('afroduuka.session.profile', '{not json');
    expect(loadSessionProfile()).toBeNull();
  });
});
