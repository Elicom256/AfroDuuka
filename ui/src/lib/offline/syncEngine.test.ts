import 'fake-indexeddb/auto';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { connectivity, type ConnectivitySnapshot } from './connectivity';
import { db } from './db';
import {
  RETRY_BASE_DELAY_MS,
  RETRY_MAX_DELAY_MS,
  SyncEngine,
  backoffDelayMs,
  isRetryDue,
  startSyncFallback,
  submitOperation,
} from './operationQueue';
import { getOperation } from './outbox';
import type { SyncOperation } from './types';

const context = { businessId: 7, branchId: 3 };

const snapshot = (partial: Partial<ConnectivitySnapshot>): ConnectivitySnapshot => ({
  online: true,
  apiReachable: true,
  checking: false,
  lastCheckedAt: null,
  ...partial,
});

const operation = (partial: Partial<SyncOperation> = {}): SyncOperation => ({
  operationId: 'op-1',
  type: 'todo',
  payload: {},
  createdAt: 1_000,
  status: 'failed',
  retryCount: 0,
  lastError: 'down',
  lastErrorAt: 1_000,
  businessId: null,
  branchId: null,
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

afterEach(() => {
  vi.restoreAllMocks();
});

describe('backoffDelayMs', () => {
  it('backs off exponentially from the base delay', () => {
    expect(backoffDelayMs(0)).toBe(RETRY_BASE_DELAY_MS);
    expect(backoffDelayMs(1)).toBe(RETRY_BASE_DELAY_MS * 2);
    expect(backoffDelayMs(2)).toBe(RETRY_BASE_DELAY_MS * 4);
  });

  it('caps at the maximum so a long outage does not park work forever', () => {
    expect(backoffDelayMs(50)).toBe(RETRY_MAX_DELAY_MS);
  });

  it('treats nonsense retry counts as zero rather than emitting NaN', () => {
    expect(backoffDelayMs(-3)).toBe(RETRY_BASE_DELAY_MS);
    expect(backoffDelayMs(Number.NaN)).toBe(RETRY_BASE_DELAY_MS);
  });
});

describe('isRetryDue', () => {
  it('is never due for a non-failed operation', () => {
    expect(isRetryDue(operation({ status: 'pending' }), 10_000_000)).toBe(false);
  });

  it('holds a failed operation until its backoff window elapses', () => {
    expect(isRetryDue(operation(), 1_000 + RETRY_BASE_DELAY_MS - 1)).toBe(false);
    expect(isRetryDue(operation(), 1_000 + RETRY_BASE_DELAY_MS)).toBe(true);
  });

  it('uses the higher retry count for the delay', () => {
    const op = operation({ retryCount: 1 });
    expect(isRetryDue(op, 1_000 + RETRY_BASE_DELAY_MS)).toBe(false);
    expect(isRetryDue(op, 1_000 + RETRY_BASE_DELAY_MS * 2)).toBe(true);
  });

  it('falls back to createdAt when no failure timestamp is recorded', () => {
    expect(isRetryDue(operation({ lastErrorAt: null }), 1_000 + RETRY_BASE_DELAY_MS)).toBe(true);
  });
});

describe('SyncEngine failed-operation retry', () => {
  it('retries a failed operation once its backoff window has elapsed', async () => {
    const engine = new SyncEngine();
    let failing = true;
    engine.registerHandler('todo', {
      send: async () => {
        if (failing) throw new Error('API down');
        return { serverId: 9 };
      },
    });

    const { localId } = await submitOperation('todo', { title: 'a' }, context);

    const first = await engine.flush();
    expect(first).toMatchObject({ attempted: 1, synced: 0, failed: 1 });

    const tooSoon = await engine.flush();
    expect(tooSoon).toMatchObject({ attempted: 0, synced: 0, failed: 0 });
    expect((await getOperation(localId))?.status).toBe('failed');

    await db.operations.update(localId, {
      lastErrorAt: Date.now() - RETRY_MAX_DELAY_MS - 1_000,
    });
    failing = false;

    const recovered = await engine.flush();
    expect(recovered).toMatchObject({ attempted: 1, synced: 1, failed: 0 });
    expect((await getOperation(localId))?.status).toBe('synced');
  });
});

describe('SyncEngine snapshot', () => {
  it('toggles running and records the last result, notifying subscribers', async () => {
    const engine = new SyncEngine();
    const listener = vi.fn();
    const unsubscribe = engine.subscribe(listener);

    expect(engine.getSnapshot()).toMatchObject({ running: false, lastResult: null });

    const pending = engine.flush();
    expect(engine.getSnapshot().running).toBe(true);

    await pending;

    expect(engine.getSnapshot().running).toBe(false);
    expect(engine.getSnapshot().lastResult).not.toBeNull();
    expect(listener).toHaveBeenCalled();

    unsubscribe();
  });
});

describe('startSyncFallback', () => {
  const stubEngine = () => {
    const flush = vi.fn().mockResolvedValue({});
    return { engine: { flush } as unknown as SyncEngine, flush };
  };

  it('flushes once on start, covering app-open', () => {
    const { engine, flush } = stubEngine();
    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(snapshot({ apiReachable: true }));

    const dispose = startSyncFallback({ engine });

    expect(flush).toHaveBeenCalledTimes(1);
    dispose();
  });

  it('stays idle on start while the API is known to be down', () => {
    const { engine, flush } = stubEngine();
    vi.spyOn(connectivity, 'getSnapshot').mockReturnValue(snapshot({ apiReachable: false }));

    const dispose = startSyncFallback({ engine });

    expect(flush).not.toHaveBeenCalled();
    dispose();
  });

  it('flushes when connectivity recovers, but not while it stays down', () => {
    const { engine, flush } = stubEngine();
    let reachable = false;
    vi.spyOn(connectivity, 'getSnapshot').mockImplementation(() =>
      snapshot({ apiReachable: reachable }),
    );
    let listener: (() => void) | null = null;
    vi.spyOn(connectivity, 'subscribe').mockImplementation((fn: () => void) => {
      listener = fn;
      return () => {};
    });

    const dispose = startSyncFallback({ engine, flushOnStart: false });
    expect(flush).not.toHaveBeenCalled();

    const notify = () => {
      listener?.();
    };

    notify();
    expect(flush).not.toHaveBeenCalled();

    reachable = true;
    notify();
    expect(flush).toHaveBeenCalledTimes(1);

    dispose();
    reachable = false;
    notify();
    reachable = true;
    notify();
    expect(flush).toHaveBeenCalledTimes(1);
  });
});
