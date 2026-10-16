/**
 * The offline-aware operation layer.
 *
 * This is the seam between the UI and the outbox. A caller does not talk to Dexie or
 * to `fetch` directly: it hands an operation to {@link submitOperation} / {@link
 * submitSale} when it already knows the write cannot reach the server, and the layer
 * persists it durably. The eventual sync engine ({@link SyncEngine}) is the *only*
 * thing allowed to turn a queued operation into a synced one.
 *
 * The contract this module deliberately keeps:
 *
 *  - **No fake success.** Nothing here marks an operation `synced`. Without a
 *    registered handler, {@link SyncEngine.flush} leaves every operation `pending` and
 *    reports it as skipped. The backend has no idempotency contract yet (see
 *    offline.md §7), so pretending a queued sale is confirmed would be a lie.
 *  - **No silent loss.** A connection failure is a reason to persist locally, never a
 *    reason to drop the write (offline.md §3).
 *  - **The online path is untouched.** When the API is reachable the caller keeps
 *    using the existing RTK Query mutations; this module only handles the fallback.
 */

import { connectivity } from './connectivity';
import {
  enqueueOperation,
  enqueueSale,
  listOperations,
  markFailed,
  markSynced,
  markSyncing,
  type EnqueueSaleInput,
} from './outbox';
import type {
  LocalSale,
  OperationContext,
  OperationType,
  SaleOperationPayload,
  SyncOperation,
} from './types';

/** The result of locally persisting an operation. `queued` is always true on resolve. */
export interface SubmitResult<TPayload = unknown> {
  queued: boolean;
  localId: string;
  operation: SyncOperation<TPayload>;
}

export interface SaleSubmitResult extends SubmitResult<SaleOperationPayload> {
  sale: LocalSale;
}

/**
 * True when a failure came from the transport rather than the server.
 *
 * RTK Query reports a request that never got an HTTP response as `FETCH_ERROR` (DNS,
 * connection refused, CORS, offline) or `TIMEOUT_ERROR`. A `422`/`500` with `data` is a
 * server answer and must not be queued — retrying it offline would just replay a
 * rejection. This distinction is what keeps a validation error visible and a dead
 * network recoverable.
 */
export function isTransportError(error: unknown): boolean {
  if (typeof error !== 'object' || error === null) return false;
  const status = (error as { status?: unknown }).status;
  return status === 'FETCH_ERROR' || status === 'TIMEOUT_ERROR';
}

/**
 * Whether the last connectivity signal says the API cannot be reached right now.
 *
 * `apiReachable === false` is a failed health probe and `online === false` is the
 * browser's own certainty. `null` (no probe yet) is treated as reachable so the first
 * attempt still goes to the server rather than queuing work that may not need to wait.
 */
export function isApiUnreachable(): boolean {
  const snapshot = connectivity.getSnapshot();
  return snapshot.apiReachable === false || !snapshot.online;
}

/**
 * Extract the tenant context from the logged-in user (`GET /users/me`).
 *
 * The API returns `business.id` / `business_id` and `business_branch_id` in different
 * shades depending on the endpoint, so both are accepted. Anything that is not a
 * positive number becomes `null` rather than `NaN`, because a bad tenant id silently
 * attached to an operation would be worse than a missing one.
 */
export function operationContextFromUser(
  user:
    | {
        business?: { id?: number | string | null } | null;
        business_id?: number | string | null;
        business_branch_id?: number | string | null;
        business_branch?: { id?: number | string | null } | null;
      }
    | null
    | undefined,
): OperationContext {
  const asId = (value: unknown): number | null => {
    const n = Number(value);
    return Number.isFinite(n) && n > 0 ? n : null;
  };

  return {
    businessId: asId(user?.business?.id ?? user?.business_id),
    branchId: asId(user?.business_branch_id ?? user?.business_branch?.id),
  };
}

/**
 * Durably queue a non-sale operation (`inventory`, `customer`, `todo`).
 *
 * Named `submit` in todayswork.md §3.4; the explicit suffix keeps the barrel export
 * unambiguous. Resolves only after the IndexedDB transaction commits, so a caller can
 * honestly tell the user the change "was saved on this device, pending sync".
 */
export async function submitOperation<TPayload>(
  type: OperationType,
  payload: TPayload,
  context: OperationContext,
  options: { operationId?: string; createdAt?: number } = {},
): Promise<SubmitResult<TPayload>> {
  const operation = await enqueueOperation(type, payload, context, options);
  return { queued: true, localId: operation.operationId, operation };
}

/**
 * Durably queue a POS sale.
 *
 * Delegates to {@link enqueueSale}, which writes the sale, every line item, the local
 * stock adjustment per line and the outbox entry in one transaction. Use this rather
 * than {@link submitOperation} so the four tables cannot diverge.
 */
export async function submitSale(input: EnqueueSaleInput): Promise<SaleSubmitResult> {
  const { operation, sale } = await enqueueSale(input);
  return { queued: true, localId: operation.operationId, operation, sale };
}

/**
 * A registered transport for one operation type.
 *
 * `send` is where a future backend contract lives. It must be idempotent on
 * `operation.operationId` for retries to be safe; that guarantee does not exist yet
 * and is the documented blocker on end-to-end offline sync (offline.md §7).
 */
export interface OperationHandler<TPayload = unknown> {
  send(operation: SyncOperation<TPayload>): Promise<{ serverId?: number | null } | void>;
}

export interface FlushSummary {
  /** Operations a handler was asked to send. */
  attempted: number;
  synced: number;
  failed: number;
  /** Operations left `pending` because no handler is registered for their type. */
  skipped: number;
  /** True when another flush was already running, so this call did no work. */
  alreadyRunning: boolean;
}

function describeError(error: unknown): string {
  if (error instanceof Error) return error.message;
  if (typeof error === 'string') return error;
  try {
    return JSON.stringify(error);
  } catch {
    return 'Unknown sync error';
  }
}

/** Base delay before a failed operation is re-attempted (10s). */
export const RETRY_BASE_DELAY_MS = 10_000;

/** Cap on the exponential backoff so a long outage does not park work forever (15min). */
export const RETRY_MAX_DELAY_MS = 15 * 60_000;

/**
 * Exponential backoff for a retrying operation, capped at {@link RETRY_MAX_DELAY_MS}.
 *
 * `retryCount` 0 → base, 1 → base*2, 2 → base*4 … Never negative, never NaN.
 */
export function backoffDelayMs(retryCount: number): number {
  const n = Number.isFinite(retryCount) ? Math.max(0, Math.floor(retryCount)) : 0;
  return Math.min(RETRY_MAX_DELAY_MS, RETRY_BASE_DELAY_MS * 2 ** n);
}

/**
 * Whether a `failed` operation is due to be re-attempted.
 *
 * A failed operation does not stay `pending`; it sits in `failed` until its backoff
 * window has elapsed. Anything other than `failed` is not due.
 */
export function isRetryDue(operation: SyncOperation, now: number = Date.now()): boolean {
  if (operation.status !== 'failed') return false;
  const lastErrorAt = operation.lastErrorAt ?? operation.createdAt;
  return now - lastErrorAt >= backoffDelayMs(operation.retryCount);
}

/**
 * A snapshot of the engine's run state.
 *
 * `running` is true while a flush is in flight, so a UI can show a spinner without
 * polling. `lastResult` is the most recent {@link FlushSummary}.
 */
export interface SyncEngineSnapshot {
  running: boolean;
  lastResult: FlushSummary | null;
  lastRanAt: number | null;
}

const INITIAL_SNAPSHOT: SyncEngineSnapshot = {
  running: false,
  lastResult: null,
  lastRanAt: null,
};

/**
 * The sync engine.
 *
 * It owns the only transition from `pending` to `synced`. Its job is deliberately
 * narrow: run each pending operation through the handler registered for its type,
 * record the outcome, and never guess. With no handlers registered it is inert —
 * `flush()` reports every operation as skipped and changes nothing.
 *
 * It also retries failed operations whose backoff window has elapsed, so an outage
 * that ends after a few minutes is recovered without the user doing anything.
 */
export class SyncEngine {
  private readonly handlers = new Map<OperationType, OperationHandler>();
  private readonly listeners = new Set<() => void>();
  private snapshot: SyncEngineSnapshot = INITIAL_SNAPSHOT;
  private activeFlush: Promise<FlushSummary> | null = null;

  subscribe = (listener: () => void): (() => void) => {
    this.listeners.add(listener);
    return () => {
      this.listeners.delete(listener);
    };
  };

  getSnapshot = (): SyncEngineSnapshot => this.snapshot;

  private patch(partial: Partial<SyncEngineSnapshot>): void {
    this.snapshot = { ...this.snapshot, ...partial };
    this.listeners.forEach((listener) => listener());
  }

  registerHandler(type: OperationType, handler: OperationHandler): void {
    this.handlers.set(type, handler);
  }

  unregisterHandler(type: OperationType): void {
    this.handlers.delete(type);
  }

  hasHandler(type: OperationType): boolean {
    return this.handlers.has(type);
  }

  isRunning(): boolean {
    return this.activeFlush !== null;
  }

  /**
   * Send every pending operation through its handler, plus any `failed` operation
   * whose backoff window has elapsed.
   *
   * Concurrent callers share one run: the second sees {@link FlushSummary.alreadyRunning}
   * instead of starting a parallel flush that could double-send. Operations with no
   * handler stay `pending` and are counted as skipped. The summary always reflects
   * the real outcome — a partial failure counts `synced` and `failed` separately and
   * never reports a false `synced`.
   */
  flush(): Promise<FlushSummary> {
    if (this.activeFlush) {
      return this.activeFlush.then((summary) => ({ ...summary, alreadyRunning: true }));
    }

    this.patch({ running: true });

    this.activeFlush = this.runFlush()
      .then((summary) => {
        this.patch({ running: false, lastResult: summary, lastRanAt: Date.now() });
        return summary;
      })
      .catch((error: unknown) => {
        this.patch({ running: false });
        throw error;
      })
      .finally(() => {
        this.activeFlush = null;
      });

    return this.activeFlush;
  }

  private async runFlush(): Promise<FlushSummary> {
    const summary: FlushSummary = {
      attempted: 0,
      synced: 0,
      failed: 0,
      skipped: 0,
      alreadyRunning: false,
    };

    const pending = await listOperations({ status: 'pending' });
    const retryable = (await listOperations({ status: 'failed' })).filter((op) =>
      isRetryDue(op),
    );
    const operations = [...pending, ...retryable];

    for (const operation of operations) {
      const handler = this.handlers.get(operation.type);
      if (!handler) {
        // A failed operation with no handler is not "pending" work — leave it failed.
        if (operation.status === 'pending') summary.skipped += 1;
        continue;
      }

      summary.attempted += 1;
      await markSyncing(operation.operationId);
      try {
        const result = await handler.send(operation);
        await markSynced(operation.operationId, result?.serverId ?? null);
        summary.synced += 1;
      } catch (error) {
        await markFailed(operation.operationId, describeError(error));
        summary.failed += 1;
      }
    }

    return summary;
  }
}

export const syncEngine = new SyncEngine();

export interface SyncFallbackOptions {
  engine?: SyncEngine;
  /** Flush once when the fallback starts, covering app-open. Defaults to true. */
  flushOnStart?: boolean;
}

/**
 * Drive sync from app lifecycle instead of relying on the Background Sync API.
 *
 * The brief (offline.md §7) calls for a reliable app-open/reconnect fallback because
 * Background Sync support is uneven. This starts one flush when it is mounted
 * (app-open) and another each time connectivity transitions from unreachable back to
 * reachable. It never flushes while the API is known to be down, so an outage is not
 * turned into a burst of doomed requests.
 *
 * Returns a disposer that detaches the listener. With no handlers registered the
 * engine is inert, so this is safe to mount before any transport exists.
 */
export function startSyncFallback(options: SyncFallbackOptions = {}): () => void {
  const engine = options.engine ?? syncEngine;
  let disposed = false;
  let wasUnreachable = isApiUnreachable();

  const flushIfReachable = (): void => {
    if (disposed || isApiUnreachable()) return;
    void engine.flush();
  };

  if (options.flushOnStart !== false) flushIfReachable();

  const unsubscribe = connectivity.subscribe(() => {
    if (disposed) return;
    const unreachableNow = isApiUnreachable();
    const recovered = wasUnreachable && !unreachableNow;
    wasUnreachable = unreachableNow;
    if (recovered) flushIfReachable();
  });

  return () => {
    disposed = true;
    unsubscribe();
  };
}
