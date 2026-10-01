/**
 * Every role dashboard is mounted at /dashboard by AppRoutes, which selects the
 * correct route tree from the logged-in user's role. URLs therefore do not carry
 * the role, and a hardcoded '/dashboard/...' link resolves for every role.
 *
 * The previous implementation returned `/${role.toLowerCase()}/dashboard`. That
 * made each hardcoded '/dashboard/...' link in the app unresolvable, and because
 * the role trees used the value as an absolute child path nested under '/*',
 * React Router rejected the tree outright.
 */
export const DASHBOARD_PREFIX = '/dashboard';
