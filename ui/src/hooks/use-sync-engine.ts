import { useCallback, useEffect } from 'react';
import { useSyncExternalStore } from 'react';
import {
  startSyncFallback,
  syncEngine,
  type FlushSummary,
  type SyncEngineSnapshot,
} from '@/lib/offline/operationQueue';

/**
 * Reactive view of the sync engine's run state plus a manual `flush`.
 *
 * Uses `useSyncExternalStore` over the engine's stable subscribe/getSnapshot pair, so
 * it re-renders only when a flush starts or finishes — not on every queued operation.
 */
export function useSyncEngine(): SyncEngineSnapshot & {
  flush: () => Promise<FlushSummary>;
} {
  const snapshot = useSyncExternalStore(
    syncEngine.subscribe,
    syncEngine.getSnapshot,
    syncEngine.getSnapshot,
  );

  const flush = useCallback(() => syncEngine.flush(), []);

  return { ...snapshot, flush };
}

/**
 * Mount the app-open/reconnect flush fallback for as long as the component is alive.
 *
 * Kept separate from {@link useSyncEngine} so a status widget can read sync state
 * without taking on the responsibility of triggering sync (a component that merely
 * renders a badge should not start network work).
 */
export function useSyncFallback(): void {
  useEffect(() => startSyncFallback(), []);
}
