import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it } from 'vitest';
import { db } from './db';
import { matchMutation } from './mutationRegistry';
import { queueIfTransportError } from './offlineWrite';
import { listOperations } from './outbox';
import { saveSessionProfile } from './sessionProfile';
import type { OperationType } from './types';

/**
 * One row per domain the write seam is allowed to queue.
 *
 * A transport failure stores exactly one pending operation. The same submit
 * again does not add a second. A 422 is the server's answer and stays unqueued.
 */
const DOMAINS: { type: OperationType; method: string; url: string; body: Record<string, unknown>; flat?: boolean }[] = [
  { type: 'todo', method: 'POST', url: '/users/todos', body: { title: 'Count stock' }, flat: true },
  { type: 'customer', method: 'POST', url: '/dashboard/customers', body: { name: 'Amina', phone: '0700000000' }, flat: true },
  { type: 'purchase', method: 'POST', url: '/purchases/branch-purchases', body: { items: [{ product_id: 1, quantity: 1 }] } },
  { type: 'sale_return', method: 'POST', url: '/returns/sale-returns', body: { items: [{ sale_item_id: 1, quantity: 1 }] } },
  { type: 'purchase_return', method: 'POST', url: '/returns/purchase-returns', body: { items: [{ purchase_item_id: 1, quantity: 1 }] } },
  { type: 'product', method: 'POST', url: '/products', body: { name: 'Rice' } },
  { type: 'supplier', method: 'POST', url: '/dashboard/suppliers', body: { firstname: 'Amina', phone: '0700000001' } },
  { type: 'worker', method: 'POST', url: '/dashboard/workers', body: { firstname: 'Peter', phone: '0700000002' } },
  { type: 'order', method: 'POST', url: '/sale-orders', body: { items: [{ product_id: 1, quantity: 1 }] } },
  { type: 'quotation', method: 'POST', url: '/quotations', body: { items: [{ product_id: 1, quantity: 1 }] } },
  { type: 'expense', method: 'POST', url: '/expenses/branch-expenses', body: { amount: 15000 } },
  { type: 'promotion', method: 'POST', url: '/promotions', body: { title: 'Market day' } },
  { type: 'coupon', method: 'POST', url: '/coupons', body: { discount_value: 500 } },
];

const REFUSED = [
  ['POST', '/pos/checkout'],
  ['DELETE', '/purchases/branch-purchases/4'],
  ['DELETE', '/returns/sale-returns/4'],
  ['DELETE', '/expenses/branch-expenses/4'],
  ['DELETE', '/dashboard/workers/4'],
  ['POST', '/messages'],
];

beforeEach(async () => {
  await db.operations.clear();
  localStorage.clear();
  saveSessionProfile({ data: { business: { id: 7 }, business_branch_id: 3 } });
});

describe('offline write matrix', () => {
  it.each(DOMAINS)('queues one $type create on a transport failure and ignores a 422', async (domain) => {
    const args = { url: domain.url, method: domain.method, body: domain.body };
    const transport = { status: 'FETCH_ERROR' as const, error: 'Failed to fetch' };

    const first = await queueIfTransportError(transport, args);
    const second = await queueIfTransportError(transport, args);

    expect(first).toMatchObject({ data: { syncStatus: 'pending', offline: true } });
    expect(second && 'data' in second && second.data.local_id).toBe(first && 'data' in first ? first.data.local_id : '');

    const [operation] = await listOperations({ type: domain.type });
    expect(operation.status).toBe('pending');
    if (domain.flat) {
      expect(operation.payload).toEqual(domain.body);
    } else {
      expect(operation.payload).toEqual({ action: 'create', id: null, body: domain.body });
    }
    expect(await listOperations({ type: domain.type })).toHaveLength(1);

    const rejected = await queueIfTransportError(
      { status: 422, data: { message: 'The given data was invalid.' } },
      args,
    );
    expect(rejected).toBeNull();
    expect(await listOperations({ type: domain.type })).toHaveLength(1);
  });

  it.each(REFUSED)('does not queue %s %s', (method, url) => {
    expect(matchMutation(method, url)).toBeNull();
  });
});
