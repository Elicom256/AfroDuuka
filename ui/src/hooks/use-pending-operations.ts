import { useLiveQuery } from 'dexie-react-hooks';
import { db } from '@/lib/offline/db';
import { countOperations, type OperationCounts } from '@/lib/offline/outbox';

/** The zeroed count used before the first IndexedDB read resolves. */
export const EMPTY_OPERATION_COUNTS: OperationCounts = {
  pending: 0,
  syncing: 0,
  synced: 0,
  failed: 0,
  total: 0,
  active: 0,
};

/**
 * Reactive operation counts, driven by Dexie's live query so the number changes the
 * moment the outbox changes — no polling, no Redux wiring.
 */
export function usePendingOperations(): OperationCounts {
  return useLiveQuery(countOperations, [], EMPTY_OPERATION_COUNTS) ?? EMPTY_OPERATION_COUNTS;
}

/**
 * Timestamp of the most recent server-confirmed operation, or null if none yet.
 * Derived from the outbox rather than a separate counter so it cannot drift.
 */
export function useLastSyncedAt(): number | null {
  const value = useLiveQuery(
    async () => {
      const synced = await db.operations.where('status').equals('synced').toArray();
      const latest = synced.reduce((max, op) => Math.max(max, op.syncedAt ?? 0), 0);
      return latest > 0 ? latest : null;
    },
    [],
    null,
  );
  return value ?? null;
}
