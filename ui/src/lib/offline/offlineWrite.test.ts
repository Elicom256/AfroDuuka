import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it } from 'vitest';
import { db } from './db';
import { matchMutation } from './mutationRegistry';
import { queueIfTransportError, queueMutation } from './offlineWrite';
import { saveSessionProfile } from './sessionProfile';
import { listOperations } from './outbox';

beforeEach(async () => {
  await db.operations.clear();
  localStorage.clear();
  saveSessionProfile({ data: { business: { id: 7 }, business_branch_id: 3 } });
});

describe('queueMutation', () => {
  it('enqueues exactly one todo when the same create is submitted twice', async () => {
    const args = { url: '/users/todos/', method: 'POST', body: { title: 'Count stock' } };

    const first = await queueMutation(args);
    const second = await queueMutation(args);

    expect(first).toMatchObject({ data: { syncStatus: 'pending', message: 'Saved on this device — will sync' } });
    expect(second && 'data' in second && second.data.local_id).toBe(
      first && 'data' in first ? first.data.local_id : '',
    );
    expect(await listOperations({ type: 'todo' })).toHaveLength(1);
  });

  it('does not queue a mutation the registry does not own', async () => {
    const result = await queueMutation({
      url: '/plans/',
      method: 'POST',
      body: { name: 'Platform plan' },
    });

    expect(result).toBeNull();
    expect(await db.operations.count()).toBe(0);
    expect(matchMutation('POST', '/users/login')).toBeNull();
  });

  it('refuses to queue when the session has no business', async () => {
    saveSessionProfile({ data: { id: 1 } });

    const result = await queueMutation({
      url: '/dashboard/customers/',
      method: 'POST',
      body: { name: 'Amina' },
    });

    expect(result).toMatchObject({
      error: { data: { message: 'Cannot save offline without a business.' } },
    });
    expect(await db.operations.count()).toBe(0);
  });

  it('stores a customer create as the flat body the existing handler validates', async () => {
    await queueMutation({
      url: '/dashboard/customers/',
      method: 'POST',
      body: { name: 'Amina', phone: '0700000000' },
    });

    const [operation] = await listOperations({ type: 'customer' });
    expect(operation.payload).toEqual({ name: 'Amina', phone: '0700000000' });
  });

  it('does not queue a server rejection', async () => {
    const result = await queueIfTransportError(
      { status: 422, data: { message: 'The title field is required.' } },
      { url: '/users/todos/', method: 'POST', body: { title: '' } },
    );

    expect(result).toBeNull();
    expect(await db.operations.count()).toBe(0);
  });
});
