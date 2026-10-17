import { store } from '@/app/store/app/store';
import { syncQuery, type SyncBatchResponse, type SyncOperationResult } from '@/app/store/features/sync/syncQuery';
import { syncEngine } from './operationQueue';
import type { OperationHandler, BatchOperationResult } from './operationQueue';
import type { OperationType, SyncOperation } from './types';

const OPERATION_TYPES: OperationType[] = [
  'sale',
  'inventory',
  'customer',
  'todo',
  'purchase',
  'sale_return',
  'purchase_return',
  'product',
  'supplier',
  'worker',
  'order',
  'quotation',
  'expense',
  'promotion',
  'coupon',
];

function numericStatus(error: unknown): number | null {
  if (typeof error !== 'object' || error === null) return null;
  const status = (error as { status?: unknown }).status;
  return typeof status === 'number' ? status : null;
}

function serverErrorMessage(error: unknown): string {
  if (typeof error !== 'object' || error === null) return 'The sync batch was rejected.';
  const data = (error as { data?: unknown }).data;
  if (typeof data === 'object' && data !== null && 'message' in data) {
    const message = (data as { message?: unknown }).message;
    if (typeof message === 'string') return message;
  }
  return 'The sync batch was rejected.';
}

function resultError(result: SyncOperationResult): string {
  return result.error?.code ?? 'sync_failed';
}

export function createSyncBatchHandler(
  businessId: number,
  sendBatch: (operations: SyncOperation[]) => Promise<SyncBatchResponse>,
): OperationHandler {
  const handler: OperationHandler = {
    canHandle: (operation) => operation.businessId === businessId,
    send: async (operation) => {
      const [result] = await handler.sendBatch!([operation]);
      if (result.status === 'synced' || result.status === 'duplicate') {
        return { serverId: result.serverId ?? null };
      }
      throw new Error(result.error ?? result.status);
    },
    sendBatch: async (operations: SyncOperation[]) => {
      let response: SyncBatchResponse;
      try {
        response = await sendBatch(operations);
      } catch (error) {
        const status = numericStatus(error);
        if (status === null || status < 400) throw error;

        const retryable = status === 401 || status >= 500;
        const code = status === 401 ? 'unauthenticated' : status === 413 ? 'batch_too_large' : 'batch_rejected';
        return operations.map((operation) => ({
          operationId: operation.operationId,
          status: 'failed' as const,
          error: `${code}: ${serverErrorMessage(error)}`,
          retryable,
        }));
      }

      const responseById = new Map(response.results.map((result) => [result.operation_id, result]));
      return operations.map((operation) => {
        const result = responseById.get(operation.operationId);
        return result ? mapResult(result) : { operationId: operation.operationId, status: 'deferred' };
      });
    },
  };

  return handler;
}

async function sendWithRtkQuery(operations: SyncOperation[]): Promise<SyncBatchResponse> {
  return store
    .dispatch(
      syncQuery.endpoints.syncBatch.initiate({
        operations: operations.map((operation) => ({
          operation_id: operation.operationId,
          type: operation.type,
          payload: operation.payload as Record<string, unknown>,
          created_at: new Date(operation.createdAt).toISOString(),
        })),
      }),
    )
    .unwrap();
}

/** Register the authenticated batch transport for every supported outbox type. */
export function registerSyncHandlers(businessId: number | null): void {
  for (const type of OPERATION_TYPES) syncEngine.unregisterHandler(type);

  if (!businessId) return;

  const handler = createSyncBatchHandler(businessId, sendWithRtkQuery);

  for (const type of OPERATION_TYPES) {
    syncEngine.registerHandler(type, handler);
  }
}

function mapResult(result: SyncOperationResult): BatchOperationResult {
  if (result.status === 'synced' || result.status === 'duplicate') {
    return {
      operationId: result.operation_id,
      status: result.status,
      serverId: result.server_id ?? null,
    };
  }

  return {
    operationId: result.operation_id,
    status: result.status,
    error: resultError(result),
    retryable: false,
  };
}
