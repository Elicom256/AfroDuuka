import { useSyncExternalStore } from 'react';
import { AlertTriangle, CheckCircle2, Loader2, WifiOff } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useOfflineStatus } from '@/hooks/use-offline-status';
import { getCacheNotice, subscribeCacheNotice } from '@/lib/offline/readCache';

/**
 * The always-on connectivity + sync status pill.
 *
 * It reports four things the operator needs at a glance (offline.md §4): online vs
 * offline, how many operations are pending, whether a sync is running, and how many
 * failed and need attention. It renders as a single compact badge rather than a banner,
 * so it never displaces dashboard or POS content (offline.md §6).
 *
 * The live region is the wrapper: the summary text is the announcement, so a screen
 * reader is told when the offline state or the counts change without stealing focus.
 */
export const OfflineStatusIndicator = () => {
  const { online, apiReachable, pendingCount, syncing, failedCount } = useOfflineStatus();
  const cacheNotice = useSyncExternalStore(subscribeCacheNotice, getCacheNotice, getCacheNotice);

  const offline = !online || apiReachable === false;

  const parts: string[] = [offline ? 'Offline' : 'Online'];
  if (offline && cacheNotice) parts.push('showing saved data');
  if (syncing) parts.push('syncing');
  if (pendingCount > 0) parts.push(`${pendingCount} pending`);
  if (failedCount > 0) parts.push(`${failedCount} failed`);
  const summary = parts.join(', ');

  const variant: 'destructive' | 'warning' | 'secondary' | 'outline' | 'success' =
    failedCount > 0
      ? 'destructive'
      : offline
        ? 'warning'
        : syncing
          ? 'secondary'
          : pendingCount > 0
            ? 'outline'
            : 'success';

  const Icon = failedCount > 0 ? AlertTriangle : offline ? WifiOff : syncing ? Loader2 : CheckCircle2;

  return (
    <div role='status' aria-live='polite' className='inline-flex'>
      <Badge variant={variant} className='gap-1.5'>
        <Icon
          aria-hidden='true'
          className={`h-3.5 w-3.5 ${syncing && !offline && failedCount === 0 ? 'animate-spin' : ''}`}
        />
        {summary}
      </Badge>
    </div>
  );
};
