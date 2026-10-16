import Dexie, { type EntityTable } from 'dexie';
import type {
  LocalSale,
  LocalSaleItem,
  LocalStockAdjustment,
  SyncOperation,
} from './types';

/**
 * The device-local IndexedDB database.
 *
 * Schema upgrades are add-only: bump the version and add a `.upgrade()` only when a
 * migration is unavoidable, because a downgrade (a user opening an older build after
 * running a newer one) must never throw. The outbox and the local sale write-model are
 * deliberately separate tables so a sale can be persisted atomically with its line
 * items, stock adjustments and outbox entry in a single transaction.
 *
 * `operations` indexes:
 *  - `operationId` (primary key) — the idempotency/dedupe key.
 *  - `status`, `type`, `[type+status]` — cheap filtered reads and counts.
 *  - `businessId`, `branchId` — tenant isolation when listing.
 */
export class OfflineDatabase extends Dexie {
  operations!: EntityTable<SyncOperation, 'operationId'>;
  sales!: EntityTable<LocalSale, 'localId'>;
  saleItems!: EntityTable<LocalSaleItem, 'id'>;
  stockAdjustments!: EntityTable<LocalStockAdjustment, 'id'>;

  constructor() {
    super('afroduuka-offline');

    this.version(1).stores({
      operations:
        'operationId, type, status, createdAt, [type+status], businessId, branchId',
      sales: 'localId, operationId, businessId, branchId, syncStatus, createdAt',
      saleItems: '++id, saleLocalId, productId',
      stockAdjustments:
        '++id, saleLocalId, productId, syncStatus, operationId, businessId, branchId',
    });
  }
}

export const db = new OfflineDatabase();

/**
 * True for the two storage-pressure errors a browser raises when IndexedDB is full or
 * eviction is blocked. Callers show a visible message rather than silently dropping the
 * operation (offline.md §3: "Handle storage errors and quota failures visibly").
 */
export function isStorageFailure(error: unknown): boolean {
  if (error instanceof Dexie.QuotaExceededError) return true;
  return (
    error instanceof Error &&
    (error.name === 'QuotaExceededError' || error.name === 'InvalidStateError')
  );
}
