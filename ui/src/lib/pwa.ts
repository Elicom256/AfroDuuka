import { registerSW } from 'virtual:pwa-register';
import { toast } from 'sonner';
import { isPosSessionActive } from './posRoute';

/**
 * Service-worker registration with a POS-safe update policy.
 *
 * `registerType: 'prompt'` (see vite.config.ts) means a new service worker installs and
 * then *waits* — the page is never reloaded behind the user's back. Applying the update
 * is our call, which is what lets a cashier finish a sale first (offline.md §2).
 */

type UpdateServiceWorker = (reloadPage?: boolean) => Promise<void>;

let updateServiceWorker: UpdateServiceWorker | undefined;

/**
 * Register the service worker. No-op in dev and in environments without a `window`
 * (the app is a client-only SPA, so this is only a guard for safety).
 */
export function setupServiceWorker(): void {
  if (typeof window === 'undefined') return;

  // Dev serves the SPA shell for /sw.js. A worker registered earlier on this
  // origin (a production build opened at http://localhost) then stays in
  // control, intercepts /api calls, and the page reports
  // "TypeError: Failed to fetch" for a request the server already answered.
  if (import.meta.env.DEV) {
    if ('serviceWorker' in navigator) {
      void navigator.serviceWorker.getRegistrations().then((registrations) => {
        registrations.forEach((registration) => {
          void registration.unregister();
        });
      });
    }
    return;
  }

  updateServiceWorker = registerSW({
    immediate: true,
    onNeedRefresh() {
      // On the POS we simply do not prompt: the waiting worker stays parked until the
      // user leaves, so a sale in progress can never be reloaded out from under them.
      if (isPosSessionActive()) return;
      toast('A new version of AfroDuuka is available', {
        duration: Number.POSITIVE_INFINITY,
        action: {
          label: 'Update',
          onClick: () => void updateServiceWorker?.(true),
        },
      });
    },
    onOfflineReady() {
      toast.success('AfroDuuka is ready to work offline.');
    },
  });
}
