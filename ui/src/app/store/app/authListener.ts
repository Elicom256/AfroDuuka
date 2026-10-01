import { createListenerMiddleware, isRejectedWithValue } from '@reduxjs/toolkit';
import { clearToken, endSessionAndRedirect } from '@/lib/session';

/**
 * Turns any 401 from any of the API slices into a signed-out session.
 *
 * Each slice configures its own fetchBaseQuery, so the only place that sees
 * every request and every rejection is a listener on the store. Matching on the
 * rejected action rather than patching 67 baseQuery definitions is what keeps
 * this to one file.
 *
 * Before this existed, an expired token was indistinguishable from a working
 * one: /me 401'd, `role` came back undefined, no role branch mounted, and the
 * user was shown the public marketing homepage with a stale token still sitting
 * in localStorage.
 */
export const authListenerMiddleware = createListenerMiddleware();

/**
 * Endpoints where a 401 is an expected answer rather than a dead session, so a
 * bad password must not sign the user out from under the login form.
 *
 * /users/me is deliberately NOT exempt: a 401 from it is the clearest possible
 * statement that the stored token is dead, and the token has to be cleared.
 */
const AUTH_EXEMPT_PATHS = ['/users/login', '/users/signup'];

const rejectedWith401 = (action: unknown): boolean => {
  if (!isRejectedWithValue(action)) {
    return false;
  }

  const payload = action.payload as { status?: number; url?: string } | undefined;

  if (payload?.status !== 401) {
    return false;
  }

  return !AUTH_EXEMPT_PATHS.some((path) => (payload?.url ?? '').includes(path));
};

authListenerMiddleware.startListening({
  predicate: (action) => rejectedWith401(action),
  effect: () => {
    // Backstop against a reload loop. AppRoutes calls /users/me on every page
    // including /login, so without this a 401 there would replace the location
    // with the page it is already on, forever. AppRoutes skips the query
    // entirely when there is no token, which is what normally prevents it.
    if (window.location.pathname === '/login') {
      clearToken();
      return;
    }

    endSessionAndRedirect();
  },
});
