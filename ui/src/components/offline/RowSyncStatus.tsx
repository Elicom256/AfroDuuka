import { SyncStatusBadge } from './SyncStatusBadge';
import type { SyncStatus } from '@/lib/offline';

const KNOWN: SyncStatus[] = ['pending', 'syncing', 'synced', 'failed'];

function asStatus(status: string | null | undefined): SyncStatus | null {
  if (!status) return null;
  return KNOWN.includes(status as SyncStatus) ? (status as SyncStatus) : null;
}

/**
 * Per-row sync marker.
 *
 * A confirmed row stays unmarked. Pending, syncing, and failed rows stay visible
 * so an offline write is not mistaken for one the server has accepted.
 */
export const RowSyncStatus = ({ status }: { status?: string | null }) => {
  const known = asStatus(status);
  if (!known || known === 'synced') return null;
  return <SyncStatusBadge status={known} />;
};
