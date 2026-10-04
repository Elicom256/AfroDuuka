import { createListenerMiddleware, isRejectedWithValue } from '@reduxjs/toolkit';
import { clearToken, endSessionAndRedirect } from '@/lib/session';
import { isPublicPath } from '@/lib/routes';

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
 * These are matched against the url the base query was given, which is relative to
 * this slice's `baseUrl` of `<VITE_BASE_URL>/users` — so the value is `/login`, not
 * `/users/login`. An earlier version listed the prefixed form and consequently never
 * matched anything: a wrong password and a revoked token were indistinguishable, and
 * since only the *removal* of the exempt-list's effect is harmful, nobody noticed
 * until the listener was actually registered and a mistyped password silently wiped a
 * valid session.
 *
 * /users/me is deliberately NOT exempt: a 401 from it is the clearest possible
 * statement that the stored token is dead, and the token has to be cleared. Same for
 * /logout — a 401 there means the token was already dead before the click.
 *
 * Matched with `===` on a normalised path rather than `includes`, so that adding an
 * endpoint such as /login-history cannot silently inherit the exemption.
 */
const AUTH_EXEMPT_PATHS = ['/login', '/signup'];

/** Normalises to the same shape the caller was asked for: no query/hash, and no
 * redundant /users prefix if a caller ever passes the fully-qualified path. */
const endpointPath = (url: string): string =>
  url
    .split(/[?#]/)[0]
    .replace(/\/users$/, '')
    .replace(/\/{2,}/g, '/')
    .replace(/(.)\/$/, '$1');

const rejectedWith401 = (action: unknown): boolean => {
  if (!isRejectedWithValue(action)) {
    return false;
  }

  const payload = action.payload as { status?: number; url?: string } | undefined;

  if (payload?.status !== 401) {
    return false;
  }

  return !AUTH_EXEMPT_PATHS.includes(endpointPath(payload?.url ?? ''));
};

authListenerMiddleware.startListening({
  predicate: (action) => rejectedWith401(action),
  effect: () => {
    // A dead token has to be discarded wherever it is noticed — that part is not
    // optional, and it is what finally clears the token that logout left behind.

    // Whether to also *navigate* is a separate question, and getting it wrong is the
    // bug this listener used to cause. `window.location.replace` is a real browser
    // navigation, so calling it here from any public page threw the visitor out of
    // the page they were reading and dropped them on /login — the reported symptom,
    // and one that survived fixing AppRoutes because this code ran independently of
    // the router.
    //
    // So: a public page keeps its URL and just degrades to signed-out chrome. Only a
    // protected page has nothing to render, so only a protected page redirects.
    if (!isPublicPath(window.location.pathname)) {
      endSessionAndRedirect();
      return;
    }

    clearToken();
  },
});
