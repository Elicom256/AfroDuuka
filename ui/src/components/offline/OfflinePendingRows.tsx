import { useState } from 'react';
import { useLiveQuery } from 'dexie-react-hooks';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/app/utils/safeDate';
import { db, retryOperation, syncEngine, type SyncOperation } from '@/lib/offline';
import { RowSyncStatus } from './RowSyncStatus';

const DATE_KEY = /(^|_)(date|at)$|date$/i;

function describe(operation: SyncOperation): string {
  const record = payloadBody(operation);
  const label = record?.title ?? record?.name ?? record?.company_name ?? record?.description ?? record?.note;
  if (typeof label === 'string' && label.trim()) return label;
  return operation.type.replaceAll('_', ' ');
}

function payloadBody(operation: SyncOperation): Record<string, unknown> | null {
  const payload = operation.payload;
  if (!payload || typeof payload !== 'object') return null;
  const nested = 'body' in payload ? (payload as { body?: unknown }).body : payload;
  if (!nested || typeof nested !== 'object' || Array.isArray(nested)) return null;
  return nested as Record<string, unknown>;
}

function fieldValue(key: string, value: unknown): string {
  if (value === null || value === undefined || value === '') return '-';
  if (typeof value === 'string' && DATE_KEY.test(key)) return formatDate(value);
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') return String(value);
  return '-';
}

/**
 * Offline creates and edits that are not on the server yet.
 *
 * They sit above the page list so a refresh still shows the local id, and a
 * failed row can be retried without leaving the page. Opening a row shows the
 * saved fields, including dates, before a server id exists.
 */
export const OfflinePendingRows = () => {
  const [openId, setOpenId] = useState<string | null>(null);
  const operations =
    useLiveQuery(
      () =>
        db.operations
          .filter(
            (operation) =>
              operation.status === 'pending' || operation.status === 'failed' || operation.status === 'syncing',
          )
          .toArray(),
      [],
      [] as SyncOperation[],
    ) ?? [];

  if (operations.length === 0) return null;

  const retry = async (operationId: string) => {
    await retryOperation(operationId);
    await syncEngine.flush();
  };

  return (
    <section className='mb-4 rounded-2xl border border-border/70 bg-card p-3' aria-label='Saved on this device'>
      <p className='mb-2 text-sm font-medium'>Saved on this device</p>
      <ul className='space-y-2'>
        {operations.map((operation) => {
          const open = openId === operation.operationId;
          const fields = payloadBody(operation);
          return (
            <li key={operation.operationId} className='text-sm'>
              <div className='flex items-center justify-between gap-3'>
                <button
                  type='button'
                  className='min-w-0 truncate text-left'
                  aria-expanded={open}
                  onClick={() => setOpenId(open ? null : operation.operationId)}
                >
                  {describe(operation)}
                  <span className='ml-2 font-mono text-xs text-muted-foreground'>{operation.operationId.slice(0, 8)}</span>
                </button>
                <span className='flex items-center gap-2'>
                  <RowSyncStatus status={operation.status} />
                  {operation.status === 'failed' ? (
                    <Button type='button' size='sm' variant='outline' onClick={() => void retry(operation.operationId)}>
                      Retry
                    </Button>
                  ) : null}
                </span>
              </div>
              {open ? (
                <dl className='mt-2 grid grid-cols-[8rem_minmax(0,1fr)] gap-x-3 gap-y-1 text-xs text-muted-foreground'>
                  <dt>Local id</dt>
                  <dd className='font-mono text-foreground'>{operation.operationId}</dd>
                  {operation.serverId ? (
                    <>
                      <dt>Server id</dt>
                      <dd>{operation.serverId}</dd>
                    </>
                  ) : null}
                  {fields
                    ? Object.entries(fields).map(([key, value]) => (
                        <span key={key} className='contents'>
                          <dt>{key}</dt>
                          <dd>{fieldValue(key, value)}</dd>
                        </span>
                      ))
                    : null}
                </dl>
              ) : null}
            </li>
          );
        })}
      </ul>
    </section>
  );
};
