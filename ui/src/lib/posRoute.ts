/**
 * Route helpers for detecting an in-progress POS sale.
 *
 * Kept separate from `pwa.ts` so the logic is unit-testable without importing the
 * service-worker module, which only resolves inside a real Vite build (it depends on
 * the `virtual:pwa-register` virtual module that the test runner does not provide).
 */
export function isPosSessionActive(pathname: string = window.location.pathname): boolean {
  return pathname === '/pos' || pathname.startsWith('/pos/');
}
