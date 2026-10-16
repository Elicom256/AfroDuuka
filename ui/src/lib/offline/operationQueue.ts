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

/**
 * The sync engine.
 *
 * It owns the only transition from `pending` to `synced`. Its job is deliberately
 * narrow: run each pending operation through the handler registered for its type,
 * record the outcome, and never guess. With no handlers registered it is inert —
 * `flush()` reports every operation as skipped and changes nothing.
 */
export class SyncEngine {
  private readonly handlers = new Map<OperationType, OperationHandler>();
  private running: Promise<FlushSummary> | null = null;

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
    return this.running !== null;
  }

  /**
   * Send every pending operation through its handler.
   *
   * Concurrent callers share one run: the second sees {@link FlushSummary.alreadyRunning}
   * instead of starting a parallel flush that could double-send. Operations with no
   * handler stay `pending` and are counted as skipped.
   */
  flush(): Promise<FlushSummary> {
    if (this.running) {
      return this.running.then((summary) => ({ ...summary, alreadyRunning: true }));
    }
    this.running = this.runFlush().finally(() => {
      this.running = null;
    });
    return this.running;
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

    for (const operation of pending) {
      const handler = this.handlers.get(operation.type);
      if (!handler) {
        summary.skipped += 1;
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
