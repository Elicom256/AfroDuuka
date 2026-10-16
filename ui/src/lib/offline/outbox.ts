import { db } from './db';
import type {
  LocalSale,
  LocalSaleItem,
  LocalStockAdjustment,
  OperationContext,
  OperationType,
  SaleOperationPayload,
  SyncOperation,
  SyncStatus,
} from './types';

/** Thrown when an operation with the same `operationId` is already queued. */
export class DuplicateOperationError extends Error {
  readonly operationId: string;

  constructor(operationId: string) {
    super(`Operation ${operationId} is already in the outbox.`);
    this.name = 'DuplicateOperationError';
    this.operationId = operationId;
  }
}

/**
 * A client-generated idempotency key.
 *
 * `crypto.randomUUID()` is used when available (all evergreen browsers and Node 19+).
 * The fallback keeps the offline layer usable on older webviews where `crypto` exists
 * but `randomUUID` does not; it is never used to make a security decision, only to mint
 * a unique-enough queue key.
 */
export function generateOperationId(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  const random = Math.random().toString(36).slice(2, 10);
  return `op-${Date.now().toString(36)}-${random}`;
}

function resolveOperationId(operationId?: string): string {
  return operationId && operationId.trim().length > 0 ? operationId : generateOperationId();
}

/**
 * Durably enqueue a single operation.
 *
 * Resolves only after the IndexedDB transaction commits, so a caller can safely tell
 * the user the operation "was saved on this device" (offline.md §3). The primary-key
 * lookup inside the transaction is what prevents a duplicate row for an id already in
 * the outbox — it rejects with {@link DuplicateOperationError} instead of overwriting.
 */
export async function enqueueOperation<TPayload>(
  type: OperationType,
  payload: TPayload,
  context: OperationContext,
  options: { operationId?: string; createdAt?: number } = {},
): Promise<SyncOperation<TPayload>> {
  const operationId = resolveOperationId(options.operationId);
  const operation: SyncOperation<TPayload> = {
    operationId,
    type,
    payload,
    createdAt: options.createdAt ?? Date.now(),
    status: 'pending',
    retryCount: 0,
    lastError: null,
    lastErrorAt: null,
    businessId: context.businessId,
    branchId: context.branchId,
    serverId: null,
    syncedAt: null,
  };

  await db.transaction('rw', db.operations, async () => {
    const existing = await db.operations.get(operationId);
    if (existing) throw new DuplicateOperationError(operationId);
    await db.operations.add(operation);
  });

  return operation;
}

export interface EnqueueSaleInput {
  items: SaleOperationPayload['items'];
  payments: SaleOperationPayload['payments'];
  customerId: number | null;
  customerName?: string | null;
  note?: string;
  context: OperationContext;
  /** Display total computed by the POS. Server totals remain authoritative. */
  total: number;
  operationId?: string;
  resumedSaleId?: number;
  createdAt?: number;
}

/**
 * Persist an offline sale atomically.
 *
 * The sale row, every line item, the local stock adjustment per line, and the outbox
 * entry are written in **one** `rw` transaction over all four tables. If any part
 * fails, IndexedDB rolls the whole transaction back, so there is never a saved sale
 * without its outbox entry or vice versa (offline.md §5, "do not implement a
 * misleading partial transaction").
 */
export async function enqueueSale(
  input: EnqueueSaleInput,
): Promise<{ operation: SyncOperation<SaleOperationPayload>; sale: LocalSale }> {
  const operationId = resolveOperationId(input.operationId);
  const createdAt = input.createdAt ?? Date.now();

  const payload: SaleOperationPayload = {
    items: input.items,
    payments: input.payments,
    customer_id: input.customerId,
    ...(input.note ? { note: input.note } : {}),
    ...(input.resumedSaleId ? { sale_id: input.resumedSaleId } : {}),
  };

  const sale: LocalSale = {
    localId: operationId,
    operationId,
    businessId: input.context.businessId,
    branchId: input.context.branchId,
    customerId: input.customerId,
    note: input.note ?? null,
    total: input.total,
    customerName: input.customerName ?? null,
    syncStatus: 'pending',
    createdAt,
  };

  const saleItems: LocalSaleItem[] = input.items.map((item) => ({
    saleLocalId: operationId,
    productId: item.product_id,
    quantity: item.quantity,
    unitPrice: item.unit_price,
    discount: item.discount ?? 0,
  }));

  const adjustments: LocalStockAdjustment[] = input.items.map((item) => ({
    saleLocalId: operationId,
    productId: item.product_id,
    quantityDelta: -Math.abs(item.quantity),
    operationId,
    businessId: input.context.businessId,
    branchId: input.context.branchId,
    syncStatus: 'pending',
    createdAt,
  }));

  const operation: SyncOperation<SaleOperationPayload> = {
    operationId,
    type: 'sale',
    payload,
    createdAt,
    status: 'pending',
    retryCount: 0,
    lastError: null,
    lastErrorAt: null,
    businessId: input.context.businessId,
    branchId: input.context.branchId,
    serverId: null,
    syncedAt: null,
  };

  await db.transaction(
    'rw',
    db.operations,
    db.sales,
    db.saleItems,
    db.stockAdjustments,
    async () => {
      const existing = await db.operations.get(operationId);
      if (existing) throw new DuplicateOperationError(operationId);
      await db.sales.add(sale);
      await db.saleItems.bulkAdd(saleItems);
      await db.stockAdjustments.bulkAdd(adjustments);
      await db.operations.add(operation);
    },
  );

  return { operation, sale };
}

export interface ListOperationsFilter {
  status?: SyncStatus;
  type?: OperationType;
  businessId?: number;
  branchId?: number;
}

/** List queued operations, newest first, optionally filtered. */
export async function listOperations(
  filter: ListOperationsFilter = {},
): Promise<SyncOperation[]> {
  let operations = await db.operations.toArray();

  if (filter.status) operations = operations.filter((op) => op.status === filter.status);
  if (filter.type) operations = operations.filter((op) => op.type === filter.type);
  if (filter.businessId !== undefined) {
    operations = operations.filter((op) => op.businessId === filter.businessId);
  }
  if (filter.branchId !== undefined) {
    operations = operations.filter((op) => op.branchId === filter.branchId);
  }

  return operations.sort((a, b) => b.createdAt - a.createdAt);
}

export async function getOperation(
  operationId: string,
): Promise<SyncOperation | undefined> {
  return db.operations.get(operationId);
}

/** Move an operation to `syncing`. Returns the updated row, or undefined if missing. */
export async function markSyncing(operationId: string): Promise<void> {
  await db.operations.update(operationId, { status: 'syncing' });
}

/** Record server confirmation, optionally with the server-assigned identifier. */
export async function markSynced(
  operationId: string,
  serverId: number | null = null,
  syncedAt: number = Date.now(),
): Promise<void> {
  await db.transaction('rw', db.operations, db.sales, async () => {
    await db.operations.update(operationId, {
      status: 'synced',
      serverId,
      syncedAt,
      lastError: null,
      lastErrorAt: null,
    });
    await db.sales.update(operationId, { syncStatus: 'synced' });
  });
}

/**
 * Record a failed attempt: status `failed`, error captured, retry count bumped.
 *
 * The operation is kept, never deleted, so a failed sync is recoverable rather than a
 * silent loss (offline.md §7, "recoverable failed operations").
 */
export async function markFailed(operationId: string, error: string): Promise<void> {
  await db.transaction('rw', db.operations, async () => {
    const current = await db.operations.get(operationId);
    if (!current) return;
    await db.operations.update(operationId, {
      status: 'failed',
      lastError: error,
      lastErrorAt: Date.now(),
      retryCount: current.retryCount + 1,
    });
  });
}

/** Return a failed operation to `pending` so the next flush retries it immediately. */
export async function retryOperation(operationId: string): Promise<void> {
  await db.operations.update(operationId, {
    status: 'pending',
    lastError: null,
    lastErrorAt: null,
  });
}

/** Remove an operation (e.g. after the user discards a failed one). */
export async function removeOperation(operationId: string): Promise<void> {
  await db.transaction('rw', db.operations, async () => {
    await db.operations.delete(operationId);
  });
}

export interface OperationCounts {
  pending: number;
  syncing: number;
  synced: number;
  failed: number;
  total: number;
  /** The number of operations still needing attention: pending + syncing + failed. */
  active: number;
}

/** Count operations by status. Powers the status indicator and pending-count hooks. */
export async function countOperations(): Promise<OperationCounts> {
  const operations = await db.operations.toArray();
  const counts: OperationCounts = {
    pending: 0,
    syncing: 0,
    synced: 0,
    failed: 0,
    total: operations.length,
    active: 0,
  };
  for (const operation of operations) {
    counts[operation.status] += 1;
  }
  counts.active = counts.pending + counts.syncing + counts.failed;
  return counts;
}
