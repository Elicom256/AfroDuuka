/**
 * Collapse runs of slashes in a URL path to a single slash.
 *
 * Verification emails built from a `FRONTEND_URL` that ended in "/" produced links
 * like `https://host//verify-email`. The server still returned the SPA (nginx merges
 * slashes for the file lookup and then falls back to index.html) and the browser kept
 * the doubled slash, so the router — which matches on the literal pathname — fell
 * through to the catch-all 404. The backend no longer emits such links, but the ones
 * already sitting in inboxes cannot be re-issued, so the path is normalised once
 * before the router mounts.
 *
 * Only the path is collapsed: a query string or fragment can legitimately contain "//"
 * (an absolute URL passed as a redirect target), so they are left untouched.
 */
export function collapseSlashes(pathname: string): string {
  return pathname.replace(/\/{2,}/g, '/');
}
