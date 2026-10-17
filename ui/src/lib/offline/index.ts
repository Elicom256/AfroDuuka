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
  markPending,
  markSynced,
  markFailed,
  retryOperation,
  removeOperation,
  countOperations,
} from './outbox';
export type { EnqueueSaleInput, ListOperationsFilter, OperationCounts } from './outbox';
export { ConnectivityMonitor, connectivity, resolveHealthUrl } from './connectivity';
export type { ConnectivityOptions, ConnectivitySnapshot } from './connectivity';
export {
  SyncEngine,
  syncEngine,
  submitOperation,
  submitSale,
  isTransportError,
  isApiUnreachable,
  operationContextFromUser,
  backoffDelayMs,
  isRetryDue,
  startSyncFallback,
  RETRY_BASE_DELAY_MS,
  RETRY_MAX_DELAY_MS,
} from './operationQueue';
export { registerSyncHandlers } from './handlers';
export {
  rememberRead,
  recallRead,
  invalidateReadDomain,
  invalidateReadDomains,
  domainForUrl,
  domainForMutation,
  isCacheableGet,
  readCacheKey,
  getCacheNotice,
  subscribeCacheNotice,
} from './readCache';
export type { ReadRequest, ServedCache } from './readCache';
export type {
  FlushSummary,
  OperationHandler,
  SaleSubmitResult,
  SubmitResult,
  SyncEngineSnapshot,
  SyncFallbackOptions,
} from './operationQueue';
