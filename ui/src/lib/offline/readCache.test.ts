import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it } from 'vitest';
import { db } from './db';
import {
  domainForUrl,
  invalidateReadDomain,
  isCacheableGet,
  readCacheKey,
  recallRead,
  rememberRead,
  resetCacheNotice,
} from './readCache';

const purchases = { method: 'GET', url: '/purchases/branch-purchases/', params: undefined };
const purchaseBody = { message: 'Purchases fetched', purchases: [{ id: 4, total_amount: 10 }] };

beforeEach(async () => {
  resetCacheNotice();
  await db.readCache.clear();
});

describe('read cache', () => {
  it('serves the last successful payload for the same url', async () => {
    await rememberRead(purchases, purchaseBody);

    const served = await recallRead(purchases);

    expect(served?.body).toEqual(purchaseBody);
    expect(served?.cachedAt).toEqual(expect.any(Number));
  });

  it('keeps the payload after the in-memory copy is gone', async () => {
    await rememberRead(purchases, purchaseBody);

    const served = await db.readCache.get(readCacheKey(purchases));

    expect(served?.body).toEqual(purchaseBody);
  });

  it('does not cache a credential envelope or the session endpoint', async () => {
    await rememberRead(purchases, { token: 'secret', data: {} });
    await rememberRead({ method: 'GET', url: '/users/me' }, { data: { id: 1 } });
    await rememberRead({ method: 'POST', url: '/purchases/branch-purchases/' }, purchaseBody);

    expect(await db.readCache.count()).toBe(0);
    expect(isCacheableGet({ method: 'GET', url: '/users/login' })).toBe(false);
  });

  it('drops a domain after a successful write without touching another domain', async () => {
    await rememberRead(purchases, purchaseBody);
    await rememberRead({ method: 'GET', url: '/sales/branch-sales/' }, { sales: [] });

    await invalidateReadDomain(domainForUrl('/purchases/branch-purchases/'));

    expect(await recallRead(purchases)).toBeNull();
    expect(await recallRead({ method: 'GET', url: '/sales/branch-sales/' })).not.toBeNull();
  });

  it('distinguishes the same path with different query params', async () => {
    await rememberRead({ method: 'GET', url: '/products/', params: { business_branch_id: 1 } }, { products: [1] });
    await rememberRead({ method: 'GET', url: '/products/', params: { business_branch_id: 2 } }, { products: [2] });

    const first = await recallRead({ method: 'GET', url: '/products/', params: { business_branch_id: 1 } });
    expect(first?.body).toEqual({ products: [1] });
  });
});
