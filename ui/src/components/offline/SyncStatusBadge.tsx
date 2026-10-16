import type { ComponentProps } from 'react';
import { Badge } from '@/components/ui/badge';
import type { SyncStatus } from '@/lib/offline';

type BadgeVariant = NonNullable<ComponentProps<typeof Badge>['variant']>;

const STATUS_LABEL: Record<SyncStatus, string> = {
  pending: 'Pending',
  syncing: 'Syncing',
  synced: 'Synced',
  failed: 'Failed',
};

const STATUS_VARIANT: Record<SyncStatus, BadgeVariant> = {
  pending: 'outline',
  syncing: 'secondary',
  synced: 'success',
  failed: 'destructive',
};

/**
 * The row/table marker for a record's synchronization state.
 *
 * It reads `sync_status` from the server (`pending|syncing|synced|failed`) and renders a
 * compact badge so a locally-saved row is visually distinct from a server-confirmed one
 * without implying anything the server has not confirmed (offline.md §5).
 */
export const SyncStatusBadge = ({
  status,
  className,
}: {
  status: SyncStatus;
  className?: string;
}) => (
  <Badge variant={STATUS_VARIANT[status]} data-status={status} className={className}>
    {STATUS_LABEL[status]}
  </Badge>
);
