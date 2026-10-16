import { baseApi } from '@/app/store/baseApi';
import type { OperationType } from '@/lib/offline/types';

export interface SyncBatchOperation {
  operation_id: string;
  type: OperationType;
  payload: Record<string, unknown>;
  created_at?: string;
}

export interface SyncOperationResult {
  operation_id: string;
  status: 'synced' | 'duplicate' | 'conflict' | 'failed';
  server_id?: number | null;
  result?: Record<string, unknown> | null;
  error?: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  };
}

export interface SyncBatchResponse {
  message: string;
  results: SyncOperationResult[];
  summary: Record<SyncOperationResult['status'], number>;
}

export interface SyncBatchRequest {
  operations: SyncBatchOperation[];
}

export const syncQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    syncBatch: builder.mutation<SyncBatchResponse, SyncBatchRequest>({
      query: (body) => ({
        url: '/sync/batch',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['PosAPI', 'ProductsAPI', 'InventoryAPI', 'SalesAPI', 'CashFlowAPI', 'TodosAPI', 'CustomersAPI'],
    }),
  }),
});

export const { useSyncBatchMutation } = syncQuery;
