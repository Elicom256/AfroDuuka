/**
 * One answer to "may this page be seen without a session?", used by the router,
 * the navbar and the auth query.
 *
 * This used to be inferred from a URL prefix. `AppRoutes` asked "is this under
 * /dashboard?" and treated everything else as public, which happened to be true
 * for the routes that existed at the time. It is a trap, though: a public page
 * added later is public by not being under /dashboard, and a protected page added
 * later is protected by exactly the same accident. The list below is explicit so
 * that adding a route means adding a decision.
 *
 * Kept free of React imports so it can be unit tested directly and imported by
 * non-component code without dragging the router in.
 */

/**
 * Routes that must render for a visitor with no valid session.
 *
 * Every entry is a complete pathname, compared exactly. None of these routes have
 * children of their own — `/pricing` is nested under the `HomeLayout` outlet but is
 * still a single segment — so exact matching is both sufficient and the safer
 * choice: a prefix comparison would classify every unknown URL as public once any
 * entry were given a child, and "unknown URL" is precisely what has to fall
 * through to the 404.
 *
 * Adding a public route means adding it here as well as to the router. The
 * regression tests in `routes.test.ts` assert the two lists agree.
 */
export const PUBLIC_PATHS: readonly string[] = [
  '/',
  '/pricing',
  '/login',
  '/signup',
  '/onboarding',
  '/about',
  '/documentation',
  '/terms',
  '/privacy',
];

/**
 * Normalises a URL down to the form the lists above are written in.
 *
 * This is not decoration. The two callers disagree about what they hand over:
 * `useLocation().pathname` from react-router drops the query and hash but keeps a
 * trailing slash, and `window.location.pathname` used by the auth listener is the one
 * that decides whether to hard-navigate away. So a visitor who lands on /pricing/ —
 * which react-router serves, since its matcher tolerates the trailing slash — would be
 * judged non-public and bounced to /login, while the page in their address bar is one
 * the router treats as public.
 *
 * That is the same class of defect as the bug these helpers were introduced to fix,
 * so the comparison is made on a canonical form instead.
 */
const canonicalPath = (pathname: string): string => {
  if (!pathname) return '/';

  // Defensive: a caller passing a full URL, or a path carrying a query/hash, should
  // not silently miss every entry in the list.
  const withoutQuery = pathname.split(/[?#]/)[0];

  if (withoutQuery.length > 1 && withoutQuery.endsWith('/')) {
    return withoutQuery.replace(/\/+$/, '') || '/';
  }

  return withoutQuery || '/';
};

/**
 * Every role mounts under a single /dashboard tree, so the URL carries no role.
 * Matching the segment and not just the characters keeps an unrelated URL such as
 * /dashboard-notes from counting as a protected route.
 */
export const isDashboardPath = (pathname: string): boolean => {
  const path = canonicalPath(pathname);
  return path === '/dashboard' || path.startsWith('/dashboard/');
};

export const isPublicPath = (pathname: string): boolean => PUBLIC_PATHS.includes(canonicalPath(pathname));

/**
 * The navbar's marketing links, kept beside PUBLIC_PATHS so a link cannot point
 * somewhere the router would then treat as protected.
 */
export const PUBLIC_NAV_LINKS: ReadonlyArray<{ label: string; to: string }> = [
  { label: 'Home', to: '/' },
  { label: 'Pricing', to: '/pricing' },
  { label: 'About', to: '/about' },
  { label: 'Documentation', to: '/documentation' },
];