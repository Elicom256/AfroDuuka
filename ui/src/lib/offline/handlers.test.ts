import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { db } from './db';
import { createSyncBatchHandler } from './handlers';
import { SyncEngine } from './operationQueue';
import { getOperation, submitOperation, submitSale } from './index';
import type { SyncOperation } from './types';

const context = { businessId: 7, branchId: 3 };
const saleInput = {
  items: [{ product_id: 1, quantity: 1, unit_price: 5000, discount: 0 }],
  payments: [{ method: 'cash', amount: 5000 }],
  customerId: null,
  context,
  total: 5000,
};

const operation = (partial: Partial<SyncOperation> = {}): SyncOperation => ({
  operationId: crypto.randomUUID(),
  type: 'todo',
  payload: { title: 'Queued task' },
  createdAt: Date.now(),
  status: 'pending',
  retryCount: 0,
  lastError: null,
  lastErrorAt: null,
  businessId: 7,
  branchId: 3,
  serverId: null,
  syncedAt: null,
  ...partial,
});

beforeEach(async () => {
  await db.operations.clear();
  await db.sales.clear();
  await db.saleItems.clear();
  await db.stockAdjustments.clear();
});

describe('createSyncBatchHandler', () => {
  it('sends eligible operations together and maps partial results by operation id', async () => {
    const engine = new SyncEngine();
    const first = operation();
    const second = operation({ operationId: crypto.randomUUID() });
    const deferred = operation({ operationId: crypto.randomUUID(), businessId: 8 });
    await db.operations.bulkAdd([first, second, deferred]);
    const send = vi.fn().mockResolvedValue({
      message: 'ok',
      summary: { synced: 1, duplicate: 0, conflict: 1, failed: 0 },
      results: [
        { operation_id: first.operationId, status: 'synced', server_id: 91 },
        {
          operation_id: second.operationId,
          status: 'conflict',
          error: { code: 'price_mismatch', message: 'Price changed.' },
        },
      ],
    });
    const handler = createSyncBatchHandler(7, send);
    for (const type of ['sale', 'inventory', 'customer', 'todo'] as const) {
      engine.registerHandler(type, handler);
    }

    const summary = await engine.flush();

    expect(send).toHaveBeenCalledTimes(1);
    expect(send.mock.calls[0][0].map((entry: SyncOperation) => entry.operationId).sort()).toEqual(
      [first.operationId, second.operationId].sort(),
    );
    expect(summary).toMatchObject({ attempted: 2, synced: 1, failed: 1, skipped: 1 });
    expect(await getOperation(first.operationId)).toMatchObject({ status: 'synced', serverId: 91 });
    expect(await getOperation(second.operationId)).toMatchObject({
      status: 'failed',
      lastError: 'price_mismatch',
      retryable: false,
    });
    expect(await getOperation(deferred.operationId)).toMatchObject({ status: 'pending' });
  });

  it('marks a duplicate sale confirmed across the operation and linked local rows', async () => {
    const engine = new SyncEngine();
    const { operation: queued } = await submitSale(saleInput);
    const send = vi.fn().mockResolvedValue({
      message: 'ok',
      summary: { synced: 0, duplicate: 1, conflict: 0, failed: 0 },
      results: [{ operation_id: queued.operationId, status: 'duplicate', server_id: 42 }],
    });
    engine.registerHandler('sale', createSyncBatchHandler(7, send));

    const summary = await engine.flush();

    expect(summary.synced).toBe(1);
    expect(await getOperation(queued.operationId)).toMatchObject({ status: 'synced', serverId: 42 });
    expect((await db.sales.get(queued.operationId))?.syncStatus).toBe('synced');
    expect((await db.stockAdjustments.toArray()).every((adjustment) => adjustment.syncStatus === 'synced')).toBe(true);
  });

  it('leaves operations pending with retry backoff after transport failure', async () => {
    const engine = new SyncEngine();
    const queued = await submitOperation('todo', { title: 'Retry me' }, context);
    const send = vi.fn().mockRejectedValue({ status: 'FETCH_ERROR', error: 'offline' });
    engine.registerHandler('todo', createSyncBatchHandler(7, send));

    const summary = await engine.flush();
    const stored = await getOperation(queued.localId);

    expect(summary).toMatchObject({ attempted: 1, synced: 0, failed: 1 });
    expect(stored).toMatchObject({ status: 'pending', retryable: true, retryCount: 1 });
    expect(stored?.lastError).toContain('FETCH_ERROR');
  });

  it('does not submit another business’s queued operation', async () => {
    const engine = new SyncEngine();
    const queued = await submitOperation('todo', { title: 'Other tenant' }, { businessId: 8, branchId: 4 });
    const send = vi.fn();
    engine.registerHandler('todo', createSyncBatchHandler(7, send));

    const summary = await engine.flush();

    expect(send).not.toHaveBeenCalled();
    expect(summary.skipped).toBe(1);
    expect(await getOperation(queued.localId)).toMatchObject({ status: 'pending' });
  });

  it('splits batches at the server limit of 50 operations', async () => {
    const engine = new SyncEngine();
    const queued = Array.from({ length: 51 }, () => operation());
    await db.operations.bulkAdd(queued);
    const send = vi.fn(async (operations: SyncOperation[]) => ({
      message: 'ok',
      summary: { synced: operations.length, duplicate: 0, conflict: 0, failed: 0 },
      results: operations.map((entry) => ({
        operation_id: entry.operationId,
        status: 'synced' as const,
      })),
    }));
    engine.registerHandler('todo', createSyncBatchHandler(7, send));

    const summary = await engine.flush();

    expect(send).toHaveBeenCalledTimes(2);
    expect(send.mock.calls.map(([batch]) => batch.length)).toEqual([50, 1]);
    expect(summary).toMatchObject({ attempted: 51, synced: 51, failed: 0 });
  });
});
