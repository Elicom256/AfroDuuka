import 'fake-indexeddb/auto';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { connectivity, type ConnectivitySnapshot } from './connectivity';
import { db } from './db';
import {
  SyncEngine,
  isApiUnreachable,
  isTransportError,
  operationContextFromUser,
  submitOperation,
  submitSale,
} from './operationQueue';
import { DuplicateOperationError, getOperation, listOperations } from './outbox';

const context = { businessId: 7, branchId: 3 };

const saleInput = {
  items: [{ product_id: 1, quantity: 2, unit_price: 5000, discount: 0 }],
  payments: [{ method: 'cash', amount: 10000 }],
  customerId: null,
  context,
  total: 10000,
};

const snapshot = (partial: Partial<ConnectivitySnapshot>): ConnectivitySnapshot => ({
  online: true,
  apiReachable: true,
  checking: false,
  lastCheckedAt: null,
  ...partial,
});

beforeEach(async () => {
  await db.operations.clear();
  await db.sales.clear();
  await db.saleItems.clear();
  await db.stockAdjustments.clear();
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('isTransportError', () => {
  it('recognises the two transport-level failures', () => {
    expect(isTransportError({ status: 'FETCH_ERROR', error: 'x' })).toBe(true);
    expect(isTransportError({ status: 'TIMEOUT_ERROR' })).toBe(true);
  });

  it('treats a server answer as a server answer, not a transport failure', () => {
    expect(isTransportError({ status: 422, data: { message: 'invalid' } })).toBe(false);
    expect(isTransportError({ status: 500 })).toBe(false);
    expect(isTransportError(null)).toBe(false);
    expect(isTransportError('boom')).toBe(false);
  });
});

describe('operationContextFromUser', () => {
  it('reads business id and branch id from the /users/me shape', () => {
    expect(
      operationContextFromUser({ business: { id: 5 }, business_branch_id: 9 }),
    ).toEqual({ businessId: 5, branchId: 9 });
  });

  it('accepts the alternate keys and coerces numeric strings', () => {
    expect(
      operationContextFromUser({ business_id: '7', business_branch: { id: '3' } }),
    ).toEqual({ businessId: 7, branchId: 3 });
  });

  it('returns nulls rather than NaN for missing or invalid values', () => {
    expect(operationContextFromUser(null)).toEqual({ businessId: null, branchId: null });
    expect(operationContextFromUser({ business: { id: 0 }, business_branch_id: 'x' })).toEqual({
      businessId: null,
      branchId: null,
    });
  });
});

describe('isApiUnreachable', () => {
  it('is false before the first probe, so the first attempt still goes online', () => {
    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(snapshot({ apiReachable: null }));
    expect(isApiUnreachable()).toBe(false);
  });

  it('is true when the health probe failed or the browser is offline', () => {
    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(snapshot({ apiReachable: false }));
    expect(isApiUnreachable()).toBe(true);

    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(
      snapshot({ online: false, apiReachable: null }),
    );
    expect(isApiUnreachable()).toBe(true);
  });

  it('is false when the API is reachable', () => {
    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(snapshot({ apiReachable: true }));
    expect(isApiUnreachable()).toBe(false);
  });
});

describe('submitOperation', () => {
  it('reports the operation as queued and stores it pending', async () => {
    const result = await submitOperation('todo', { title: 'Order stock' }, context);

    expect(result.queued).toBe(true);
    expect(result.localId).toBe(result.operation.operationId);
    expect(await getOperation(result.localId)).toMatchObject({
      type: 'todo',
      status: 'pending',
      businessId: 7,
      branchId: 3,
    });
  });

  it('rejects a duplicate operation id instead of overwriting', async () => {
    await submitOperation('customer', { name: 'A' }, context, { operationId: 'fixed' });
    await expect(
      submitOperation('customer', { name: 'B' }, context, { operationId: 'fixed' }),
    ).rejects.toBeInstanceOf(DuplicateOperationError);
  });
});

describe('submitSale', () => {
  it('queues the sale and returns both the sale row and the operation', async () => {
    const result = await submitSale(saleInput);

    expect(result.queued).toBe(true);
    expect(result.sale).toMatchObject({
      localId: result.localId,
      total: 10000,
      syncStatus: 'pending',
    });
    expect(result.operation.type).toBe('sale');
    expect(await db.saleItems.count()).toBe(1);
    expect(await db.stockAdjustments.count()).toBe(1);
  });
});

describe('SyncEngine', () => {
  it('does not fake success when no handler is registered', async () => {
    const engine = new SyncEngine();
    await submitOperation('todo', { title: 'a' }, context);
    await submitOperation('customer', { name: 'b' }, context);

    const summary = await engine.flush();

    expect(summary).toEqual({
      attempted: 0,
      synced: 0,
      failed: 0,
      skipped: 2,
      alreadyRunning: false,
    });

    const operations = await listOperations();
    expect(operations.every((op) => op.status === 'pending')).toBe(true);
  });

  it('marks an operation synced with the server id when its handler resolves', async () => {
    const engine = new SyncEngine();
    engine.registerHandler('sale', {
      send: async () => ({ serverId: 555 }),
    });

    const { localId } = await submitSale(saleInput);
    const summary = await engine.flush();

    expect(summary).toMatchObject({ attempted: 1, synced: 1, failed: 0, skipped: 0 });
    expect(await getOperation(localId)).toMatchObject({
      status: 'synced',
      serverId: 555,
      lastError: null,
    });
    expect((await db.sales.get(localId))?.syncStatus).toBe('synced');
  });

  it('keeps a failed operation recoverable with the error recorded', async () => {
    const engine = new SyncEngine();
    engine.registerHandler('sale', {
      send: async () => {
        throw new Error('server rejected');
      },
    });

    const { localId } = await submitSale(saleInput);
    const summary = await engine.flush();

    expect(summary).toMatchObject({ attempted: 1, synced: 0, failed: 1 });
    expect(await getOperation(localId)).toMatchObject({
      status: 'failed',
      retryCount: 1,
      lastError: 'server rejected',
    });
  });

  it('runs one flush at a time rather than double-sending', async () => {
    const engine = new SyncEngine();
    let release: () => void = () => {};
    const gate = new Promise<void>((resolve) => {
      release = resolve;
    });

    engine.registerHandler('todo', {
      send: async () => {
        await gate;
        return { serverId: 1 };
      },
    });

    await submitOperation('todo', { title: 'a' }, context);

    const first = engine.flush();
    const second = engine.flush();
    release();

    const [firstSummary, secondSummary] = await Promise.all([first, second]);

    expect(firstSummary.alreadyRunning).toBe(false);
    expect(secondSummary.alreadyRunning).toBe(true);
    expect(firstSummary.synced).toBe(1);
    expect(await db.operations.count()).toBe(1);
  });
});
