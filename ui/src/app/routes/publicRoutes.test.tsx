import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, useLocation } from 'react-router-dom';

import { AppRoutes } from './AppRoutes';
import { store } from '../store/app/store';
import { authQuery } from '../store/features/auth/authQuery';
import { PUBLIC_PATHS, isPublicPath } from '@/lib/routes';
import { setToken, getToken } from '@/lib/session';
import { navigations } from '@/test/setup';

/**
 * These are the regression tests for the dead-token lockout.
 *
 * The bug: a token that the server had revoked stayed in localStorage, and
 * AppRoutes turned the resulting 401 from /users/me into a <Navigate to="/login">
 * on *every* route except /login itself. Because that branch returns a Navigate
 * instead of <Routes>, it replaced the whole page, so /, /pricing, /about,
 * /documentation, /terms and /privacy became unreachable for anyone who had ever
 * signed in on the machine. There was no way back: nothing cleared the token.
 *
 * The fix must hold two properties, and both are asserted here:
 *   1. a public path keeps its own URL, whatever the /users/me result is;
 *   2. a protected path still refuses to render without a role.
 *
 * These deliberately use the REAL app store rather than a purpose-built one. The
 * original defect was that authListenerMiddleware existed and was never registered,
 * and a test against a hand-rolled store would not have noticed that at all.
 */

/** Location has to be observed from outside AppRoutes, because the failing branch
 * returns <Navigate> in place of <Routes> and never renders its own children. */
const LocationProbe = () => {
  const { pathname } = useLocation();
  return <span data-testid='pathname'>{pathname}</span>;
};

/**
 * fetchBaseQuery hands `fetch` a Request object, not a string, so the URL has to be
 * read off `.url`. String(request) is "[object Request]", which silently fails every
 * match below — and made /users/me return 200 in every test, so the listener that
 * these tests exist to cover never ran.
 */
const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

/**
 * `meStatus` controls only the /users/me response. Everything else gets a shape the
 * public pages can actually mount with: SignUp maps over `countries`, so a bare `{}`
 * there crashes the render and the test would fail for a reason that has nothing to do
 * with routing.
 */
const stubFetch = (meStatus: number) => {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = requestUrl(input);
    const json = (body: unknown) =>
      new Response(JSON.stringify(body), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });

    if (url.includes('/users/me')) {
      return new Response(JSON.stringify({ message: 'Unauthenticated.' }), {
        status: meStatus,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    if (url.includes('/countries')) return json({ data: [] });
    if (url.includes('/business-categories')) return json([]);
    if (url.includes('/dashboard/business')) return json([]);

    return json({ data: {} });
  });

  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
};

const renderAt = (path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <LocationProbe />
        <AppRoutes />
      </MemoryRouter>
    </Provider>
  );

const currentPath = () => screen.getByTestId('pathname').textContent;

type QueryEntry = { endpointName?: string; status?: string };

/** The part of an RTK Query api slice this helper reads. Typed as its own shape rather
 *  than reached through a blanket cast, because the real type is nested one level deeper
 *  and casting straight to `Record<string, QueryEntry>` makes `.queries` look like a
 *  single entry, which silently types `Object.values` as `string[]`. */
type SessionSlice = { queries?: Record<string, QueryEntry> };

/**
 * Waits for the /users/me query to actually settle before anything is asserted.
 *
 * Without this the assertions run against the pre-response state and prove nothing:
 * a version of this suite checked `not.toBe('/login')` inside `waitFor`, which passed
 * on the very first tick — before the 403 had arrived — and then went on to assert the
 * still-correct initial path. It stayed green while the redirect bug was reintroduced
 * into AppRoutes.
 *
 * Reading the store rather than the DOM because a public page gives no visible signal
 * that its session query failed: there is nothing on screen that changes.
 *
 * Located by `endpointName` rather than by the serialised cache key, so passing
 * `{ skip: false }` in the component under test does not change the key this looks for.
 */
const waitForSessionQuery = async (expected: 'rejected' | 'fulfilled') => {
  await waitFor(() => {
    const slice = (store.getState() as Record<string, SessionSlice>)[authQuery.reducerPath];
    const entry = Object.values(slice?.queries ?? {}).find((query) => query?.endpointName === 'loggedinUser');

    expect(entry?.status).toBe(expected);
  });
};

beforeEach(async () => {
  store.dispatch(authQuery.util.resetApiState());
});

describe('public routes with no token', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it.each(PUBLIC_PATHS)('renders %s in place instead of redirecting to /login', async (path) => {
    stubFetch(200);

    renderAt(path);

    await waitFor(() => {
      expect(currentPath()).toBe(path);
    });
  });

  it('never asks who the visitor is when there is no token to send', async () => {
    // The query is skipped outright rather than asked-and-ignored. A public page that
    // depends on an auth endpoint is a public page that breaks when the api is slow or
    // down, which is what put a loader on the marketing homepage.
    //
    // Asserted as "no /users/me" rather than "no requests at all": /pricing legitimately
    // loads plans and /signup loads countries, and those must still work for a
    // signed-out visitor. What must never happen is the auth call.
    const fetchMock = stubFetch(200);

    renderAt('/pricing');

    await waitFor(() => {
      expect(currentPath()).toBe('/pricing');
    });

    const meCalls = fetchMock.mock.calls.filter(([input]) => requestUrl(input).includes('/users/me'));
    expect(meCalls).toHaveLength(0);
  });
});

describe('public routes with a dead token', () => {
  beforeEach(() => {
    setToken('revoked.token.value');
  });

  it.each(PUBLIC_PATHS)('keeps %s reachable when /users/me returns 401', async (path) => {
    stubFetch(401);

    renderAt(path);

    // The assertion is that the URL does not move. That is the whole defect: these
    // pages were reachable by direct load and then became permanently redirected.
    await waitFor(() => {
      expect(currentPath()).toBe(path);
    });
  });

  it('clears the dead token so the next load is a clean signed-out visit', async () => {
    // Step 1 of the fix: authListenerMiddleware is registered in the store. Without
    // it nothing ever cleared the token, which is how "log out" ended up
    // indistinguishable from "still signed in, but wrongly".
    stubFetch(401);

    renderAt('/about');

    await waitFor(() => {
      expect(getToken()).toBeNull();
    });
  });

  it('does not clear a token that the server still accepts', async () => {
    // The listener must key on the rejection, not merely on the fact that /me was
    // called. Clearing on success would sign people out at random.
    stubFetch(200);

    renderAt('/about');

    await waitFor(() => {
      expect(currentPath()).toBe('/about');
    });
    expect(getToken()).toBe('revoked.token.value');
  });
});

describe('protected routes', () => {
  beforeEach(() => {
    setToken('revoked.token.value');
  });

  it('sends /dashboard to /login when the token is rejected', async () => {
    // The redirect is legitimate here and must not be lost along with the bug. A
    // protected page has no role and no tree to render, so login is the only honest
    // destination.
    stubFetch(401);

    renderAt('/dashboard');

    await waitFor(() => {
      expect(currentPath()).toBe('/login');
    });
  });

  it('sends a nested dashboard path to /login as well', async () => {
    stubFetch(401);

    renderAt('/dashboard/orders');

    await waitFor(() => {
      expect(currentPath()).toBe('/login');
    });
  });
});

describe('a dead token must not move the browser', () => {
  // This block is the one that cannot be satisfied by inspecting the router alone.
  //
  // AppRoutes and authListener are two independent places that can eject a visitor,
  // and the reported bug lived in whichever one happened to be wired up. An earlier
  // version of this suite asserted only on MemoryRouter's location and passed even
  // with authListener still calling window.location.replace('/login') — the redirect
  // was real, it just happened outside the router and went unseen.
  //
  // `navigations` records every href assignment and location.replace, so this asserts
  // on what a real browser would actually do.
  beforeEach(() => {
    setToken('revoked.token.value');
  });

  it.each(PUBLIC_PATHS)('does not navigate away from %s', async (path) => {
      stubFetch(401);
      // The listener reads window.location.pathname, which is not the router's path,
      // so it has to be put where the visitor actually is.
      window.location.pathname = path;

      renderAt(path);

      await waitForSessionQuery('rejected');
      await waitFor(() => {
        expect(getToken()).toBeNull();
      });

      expect(navigations).toEqual([]);
      expect(currentPath()).toBe(path);
    }
  );

  it('does not navigate away when the dead token is noticed on a public page with a trailing slash', async () => {
    // /pricing/ is served by react-router, whose matcher tolerates the trailing
    // slash. An exact-match listener check treats it as non-public and bounces the
    // visitor off a page they are legitimately on, so canonicalisation is required.
    stubFetch(401);
    window.location.pathname = '/pricing/';

    renderAt('/pricing/');

    await waitForSessionQuery('rejected');
    await waitFor(() => {
      expect(getToken()).toBeNull();
    });

    expect(navigations).toEqual([]);
  });

  it('does navigate away from a protected page', async () => {
    // The listener is not neutered: a protected page has no role and nothing to
    // render, so clearing the token and staying put would show an empty shell.
    stubFetch(401);
    window.location.pathname = '/dashboard';

    renderAt('/dashboard');

    await waitFor(() => {
      expect(navigations).toContain('/login');
    });
    expect(getToken()).toBeNull();
  });

  it('does not sign someone out for a bad password', async () => {
    // The exempt list. A 401 from the login endpoint is the expected answer to wrong
    // credentials, not a dead session — treating it as one wipes the token of anyone
    // mid-sign-in and leaves them on a blank login form with no way to retry.
    //
    // Dispatched directly rather than rendered, and that matters: mounting AppRoutes
    // on /login also fires /users/me, whose 401 *should* clear the token. Rendering
    // would have tested the wrong rejection and passed or failed for the wrong reason.
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(JSON.stringify({ message: 'These credentials do not match.' }), {
            status: 401,
            headers: { 'Content-Type': 'application/json' },
          })
      )
    );

    await store
      .dispatch(authQuery.endpoints.login.initiate({ email: 'a@b.test', password: 'wrong' }))
      .unwrap()
      .catch(() => undefined);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(getToken()).toBe('revoked.token.value');
    expect(navigations).toEqual([]);
  });

  it('does clear the token when /users/me is rejected on the login page itself', async () => {
    // The counterpart to the test above, and the reason the two are separate. On /login
    // a dead token is discarded but the visitor is not navigated: they are already
    // where they need to be.
    stubFetch(401);
    window.location.pathname = '/login';

    renderAt('/login');

    await waitFor(() => {
      expect(getToken()).toBeNull();
    });
    expect(navigations).toEqual([]);
  });
});

describe('a token the server accepts but refuses (403)', () => {
  // 403 is not a dead token — the token is fine and the request is not allowed. The
  // auth listener only acts on 401 precisely so it will not sign somebody out for a
  // permissions problem, which makes AppRoutes' own `onDashboard` check the only thing
  // deciding where a 403 lands. Without a test here that branch is unreachable in
  // every other test in this file (a 401 clears the token first, so `hasToken` is
  // already false by the time the branch is evaluated) and would silently rot.
  beforeEach(() => {
    setToken('valid.token.value');
  });

  it('does not navigate away from a public page', async () => {
    stubFetch(403);
    window.location.pathname = '/pricing';

    renderAt('/pricing');

    await waitForSessionQuery('rejected');

    expect(navigations).toEqual([]);
    expect(currentPath()).toBe('/pricing');
    // A 403 says nothing about the credential, so the token must survive.
    expect(getToken()).toBe('valid.token.value');
  });

  it('sends a protected page to /login, which has nothing to render', async () => {
    stubFetch(403);
    window.location.pathname = '/dashboard';

    renderAt('/dashboard');

    await waitForSessionQuery('rejected');
    await waitFor(() => {
      expect(currentPath()).toBe('/login');
    });
    expect(getToken()).toBe('valid.token.value');
  });
});

describe('PUBLIC_PATHS agreement with the router', () => {
  it('every declared public path is one the router actually serves', async () => {
    // Guards the two lists from drifting: a page added to AppRoutes as public but
    // forgotten here (or the reverse) reopens the loop in one direction or the other.
    for (const path of PUBLIC_PATHS) {
      stubFetch(200);
      const { unmount } = renderAt(path);
      await waitFor(() => {
        expect(currentPath()).toBe(path);
      });
      unmount();
    }
  });
});

describe('isPublicPath', () => {
  it('accepts the declared public paths and rejects protected ones', () => {
    for (const path of PUBLIC_PATHS) {
      expect(isPublicPath(path)).toBe(true);
    }

    // /login, /signup and /onboarding are on the public list on purpose: each renders
    // its own signed-out form rather than bouncing, and a dead token must not be able
    // to hide the login screen — that is how someone got locked out before.
    expect(isPublicPath('/dashboard')).toBe(false);
    expect(isPublicPath('/dashboard/orders')).toBe(false);
    expect(isPublicPath('/settings')).toBe(false);
  });
});
