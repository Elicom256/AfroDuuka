import { useEffect, useSyncExternalStore } from 'react';
import { connectivity, type ConnectivitySnapshot } from '@/lib/offline/connectivity';

/**
 * Subscribe a component to the connectivity monitor.
 *
 * The monitor is reference-counted, so mounting this hook in several places attaches a
 * single set of browser listeners and a single background interval.
 */
export function useConnectivity(): ConnectivitySnapshot {
  const snapshot = useSyncExternalStore(
    connectivity.subscribe,
    connectivity.getSnapshot,
    connectivity.getSnapshot,
  );

  useEffect(() => {
    connectivity.start();
    return () => connectivity.stop();
  }, []);

  return snapshot;
}
