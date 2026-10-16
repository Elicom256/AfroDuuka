/**
 * Shared types for the offline-first layer.
 *
 * Local persistence is device-specific and may be evicted by the browser; it is a
 * working buffer for operations that could not reach the API, not a backup. Nothing
 * here is treated as authoritative: the Laravel API remains the source of truth once
 * an operation syncs (see offline.md §3 and §7).
 */

/**
 * The kinds of write the outbox understands.
 *
 * The initial set mirrors the domains a cashier/operator touches while a connection
 * is down: a POS sale, a stock/inventory change, a customer record, and a todo. The
 * union is intentionally narrow — a new type must be added here before it can be
 * queued, so the outbox cannot silently accumulate operations no flush handler knows
 * how to send.
 */
export type OperationType = 'sale' | 'inventory' | 'customer' | 'todo';

/** Lifecycle of a queued operation, from durable local save to server confirmation. */
export type SyncStatus = 'pending' | 'syncing' | 'synced' | 'failed';

/** Tenant context every operation carries, matching the API's `business_id`/`business_branch_id`. */
export interface OperationContext {
  businessId: number | null;
  branchId: number | null;
}

/**
 * A durable entry in the outbox.
 *
 * `operationId` is the client-generated idempotency key. The backend has no general
 * idempotency contract yet (see offline.md §7 and todayswork.md §5), so this id is the
 * stable key the eventual sync engine will present to the server to make retries safe.
 */
export interface SyncOperation<TPayload = unknown> {
  operationId: string;
  type: OperationType;
  /** Serialized operation body — the exact payload a flush handler would POST. */
  payload: TPayload;
  createdAt: number;
  status: SyncStatus;
  retryCount: number;
  /** False for server-confirmed permanent failures that require user correction. */
  retryable?: boolean;
  lastError: string | null;
  /**
   * When the most recent failure happened, if any.
   *
   * Drives retry backoff: a failed operation is only re-attempted once
   * `now - lastErrorAt >= backoffDelayMs(retryCount)`, so an unreachable API is
   * not hammered while it stays down.
   */
  lastErrorAt: number | null;
  businessId: number | null;
  branchId: number | null;
  /** Set once the server confirms the operation, if the API returns an id. */
  serverId: number | null;
  syncedAt: number | null;
}

/**
 * A line item of a locally-recorded sale.
 *
 * Stored in its own table so a sale, its items, its local stock adjustments and its
 * outbox entry can be written in one IndexedDB transaction (offline.md §5).
 */
export interface LocalSaleItem {
  id?: number;
  saleLocalId: string;
  productId: number;
  quantity: number;
  unitPrice: number;
  discount: number;
}

/** A local stock decrement recorded alongside an offline sale, applied on sync. */
export interface LocalStockAdjustment {
  id?: number;
  saleLocalId: string;
  productId: number;
  /** Signed quantity: negative decrements stock, positive restocks. */
  quantityDelta: number;
  operationId: string;
  businessId: number | null;
  branchId: number | null;
  syncStatus: SyncStatus;
  createdAt: number;
}

/**
 * A sale saved on this device but not yet confirmed by the server.
 *
 * `total` mirrors the amount the POS computed for display. It is not authoritative —
 * the server recomputes inventory and financial totals (`baseApi.ts` documents the
 * same server-authoritative stance; offline.md §7 forbids last-write-wins for money).
 */
export interface LocalSale {
  localId: string;
  operationId: string;
  businessId: number | null;
  branchId: number | null;
  customerId: number | null;
  note: string | null;
  total: number;
  customerName: string | null;
  syncStatus: SyncStatus;
  createdAt: number;
}

/** The payload shape a `sale` operation carries, matching `POST /pos/checkout`. */
export interface SaleOperationPayload {
  items: Array<{
    product_id: number;
    quantity: number;
    unit_price: number;
    discount: number;
  }>;
  payments: Array<{ method: string; amount: number }>;
  customer_id: number | null;
  note?: string;
  /** Present when resuming a held sale, per PosPage's checkout body. */
  sale_id?: number;
}
