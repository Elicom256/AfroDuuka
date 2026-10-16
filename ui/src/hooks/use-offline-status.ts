import type { ConnectivitySnapshot } from '@/lib/offline/connectivity';
import type { OperationCounts } from '@/lib/offline/outbox';
import { useConnectivity } from './use-connectivity';
import {
  EMPTY_OPERATION_COUNTS,
  useLastSyncedAt,
  usePendingOperations,
} from './use-pending-operations';

/**
 * The single reactive view of offline state the UI reads.
 *
 * Combines the browser/API connectivity signals with the outbox counts so a status
 * component needs one hook, not three (offline.md §4 lists exactly these fields).
 */
export interface OfflineStatus extends ConnectivitySnapshot {
  counts: OperationCounts;
  /** Operations waiting to be sent: pending + syncing. */
  pendingCount: number;
  /** A flush is in progress. */
  syncing: boolean;
  /** Operations that need attention after a failed attempt. */
  failedCount: number;
  lastSyncedAt: number | null;
  /** Nothing to send and nothing failed. */
  allSynced: boolean;
}

export function useOfflineStatus(): OfflineStatus {
  const connectivityState = useConnectivity();
  const counts = usePendingOperations();
  const lastSyncedAt = useLastSyncedAt();

  const pendingCount = counts.pending + counts.syncing;

  return {
    ...connectivityState,
    counts,
    pendingCount,
    syncing: counts.syncing > 0,
    failedCount: counts.failed,
    lastSyncedAt,
    allSynced: counts.active === 0,
  };
}

export { EMPTY_OPERATION_COUNTS };
