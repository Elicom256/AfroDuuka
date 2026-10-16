/**
 * Offline-first foundation public surface.
 *
 * Import from this barrel rather than deep paths so the module's internals can move
 * without touching call sites.
 */
export * from './types';
export { db, isStorageFailure, OfflineDatabase } from './db';
export {
  DuplicateOperationError,
  generateOperationId,
  enqueueOperation,
  enqueueSale,
  listOperations,
  getOperation,
  markSyncing,
  markSynced,
  markFailed,
  retryOperation,
  removeOperation,
  countOperations,
} from './outbox';
export type {
  EnqueueSaleInput,
  ListOperationsFilter,
  OperationCounts,
} from './outbox';
