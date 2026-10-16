import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it } from 'vitest';
import { db } from './db';
import {
  DuplicateOperationError,
  countOperations,
  enqueueOperation,
  enqueueSale,
  generateOperationId,
  getOperation,
  listOperations,
  markFailed,
  markSynced,
  markSyncing,
  removeOperation,
  retryOperation,
} from './outbox';
import type { SaleOperationPayload } from './types';

const context = { businessId: 7, branchId: 3 };

const saleInput = {
  items: [
    { product_id: 1, quantity: 2, unit_price: 5000, discount: 0 },
    { product_id: 2, quantity: 1, unit_price: 3000, discount: 500 },
  ],
  payments: [{ method: 'cash', amount: 12500 }],
  customerId: 42,
  customerName: 'Amina',
  note: 'counter sale',
  context,
  total: 12500,
};

beforeEach(async () => {
  await db.operations.clear();
  await db.sales.clear();
  await db.saleItems.clear();
  await db.stockAdjustments.clear();
});

describe('generateOperationId', () => {
  it('mints unique ids', () => {
    const ids = new Set(Array.from({ length: 50 }, () => generateOperationId()));
    expect(ids.size).toBe(50);
  });
});

describe('enqueueOperation', () => {
  it('durably stores a pending operation with tenant context', async () => {
    const operation = await enqueueOperation('todo', { title: 'Order stock' }, context);

    const stored = await getOperation(operation.operationId);
    expect(stored).toMatchObject({
      type: 'todo',
      status: 'pending',
      retryCount: 0,
      lastError: null,
      businessId: 7,
      branchId: 3,
      serverId: null,
      syncedAt: null,
    });
    expect(stored?.payload).toEqual({ title: 'Order stock' });
  });

  it('rejects a duplicate operation id instead of overwriting it', async () => {
    await enqueueOperation('customer', { name: 'A' }, context, { operationId: 'fixed-id' });

    await expect(
      enqueueOperation('customer', { name: 'B' }, context, { operationId: 'fixed-id' }),
    ).rejects.toBeInstanceOf(DuplicateOperationError);

    const all = await listOperations();
    expect(all).toHaveLength(1);
    expect(all[0].payload).toEqual({ name: 'A' });
  });
});

describe('enqueueSale', () => {
  it('persists the sale, its items, stock adjustments and outbox entry atomically', async () => {
    const { operation, sale } = await enqueueSale(saleInput);

    expect(operation.type).toBe('sale');
    expect(sale).toMatchObject({
      localId: operation.operationId,
      operationId: operation.operationId,
      customerId: 42,
      total: 12500,
      syncStatus: 'pending',
    });

    const items = await db.saleItems.where('saleLocalId').equals(sale.localId).toArray();
    expect(items).toHaveLength(2);
    expect(items.map((i) => i.productId).sort()).toEqual([1, 2]);

    const adjustments = await db.stockAdjustments
      .where('saleLocalId')
      .equals(sale.localId)
      .toArray();
    expect(adjustments).toHaveLength(2);
    expect(adjustments.every((a) => a.operationId === operation.operationId)).toBe(true);
    expect(adjustments.every((a) => a.quantityDelta < 0)).toBe(true);

    expect(await db.operations.count()).toBe(1);
  });

  it('carries the resumed held-sale id into the payload', async () => {
    const { operation } = await enqueueSale({ ...saleInput, resumedSaleId: 99 });
    expect((operation.payload as SaleOperationPayload).sale_id).toBe(99);
  });

  it('writes nothing when the operation id is already queued', async () => {
    await enqueueSale({ ...saleInput, operationId: 'dup' });

    await expect(enqueueSale({ ...saleInput, operationId: 'dup' })).rejects.toBeInstanceOf(
      DuplicateOperationError,
    );

    expect(await db.sales.count()).toBe(1);
    expect(await db.saleItems.count()).toBe(2);
    expect(await db.stockAdjustments.count()).toBe(2);
    expect(await db.operations.count()).toBe(1);
  });
});

describe('status transitions', () => {
  it('moves pending -> syncing -> synced and stamps server details', async () => {
    const { operation } = await enqueueSale(saleInput);

    await markSyncing(operation.operationId);
    expect((await getOperation(operation.operationId))?.status).toBe('syncing');

    await markSynced(operation.operationId, 555, 1_700_000_000_000);
    const stored = await getOperation(operation.operationId);
    expect(stored).toMatchObject({
      status: 'synced',
      serverId: 555,
      syncedAt: 1_700_000_000_000,
      lastError: null,
    });
    expect((await db.sales.get(operation.operationId))?.syncStatus).toBe('synced');
  });

  it('failed increments retryCount and keeps the error; retry returns it to pending', async () => {
    const { operation } = await enqueueSale(saleInput);

    await markFailed(operation.operationId, 'network down');
    await markFailed(operation.operationId, 'still down');

    const failed = await getOperation(operation.operationId);
    expect(failed).toMatchObject({
      status: 'failed',
      retryCount: 2,
      lastError: 'still down',
    });

    await retryOperation(operation.operationId);
    expect(await getOperation(operation.operationId)).toMatchObject({
      status: 'pending',
      lastError: null,
    });
  });
});

describe('listing, counting and removal', () => {
  it('filters by status and counts active work', async () => {
    const a = await enqueueOperation('todo', { title: 'a' }, context);
    const b = await enqueueOperation('todo', { title: 'b' }, context);
    await enqueueOperation('customer', { name: 'c' }, context);

    await markSynced(b.operationId);
    await markFailed(a.operationId, 'boom');

    const failed = await listOperations({ status: 'failed' });
    expect(failed.map((op) => op.operationId)).toEqual([a.operationId]);

    const counts = await countOperations();
    expect(counts).toMatchObject({ total: 3, pending: 1, synced: 1, failed: 1, active: 2 });
  });

  it('removes an operation', async () => {
    const operation = await enqueueOperation('todo', { title: 'a' }, context);
    await removeOperation(operation.operationId);
    expect(await getOperation(operation.operationId)).toBeUndefined();
  });
});
