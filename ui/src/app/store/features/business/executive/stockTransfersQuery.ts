import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const stockTransfersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    stockTransfers: builder.query<any, void>({
      query: () => ({ url: '/stock-transfers/', method: 'GET' }),
      providesTags: withDashboard(apiTags.stockTransfers),
    }),
    stockTransfer: builder.query<any, string>({
      query: (id) => ({ url: `/stock-transfers/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.stockTransfers),
    }),
    // A transfer moves stock between branches, so product and inventory figures
    // move with it.
    createStockTransfer: builder.mutation<any, any>({
      query: (body) => ({ url: '/stock-transfers/', method: 'POST', body }),
      invalidatesTags: withDashboard(
        apiTags.stockTransfers,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    dispatchStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/stock-transfers/${id}/dispatch`, method: 'POST' }),
      invalidatesTags: withDashboard(
        apiTags.stockTransfers,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    receiveStockTransfer: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/stock-transfers/${id}/receive`, method: 'POST', body }),
      invalidatesTags: withDashboard(
        apiTags.stockTransfers,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    cancelStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/stock-transfers/${id}/cancel`, method: 'POST' }),
      invalidatesTags: withDashboard(
        apiTags.stockTransfers,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    deleteStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/stock-transfers/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(
        apiTags.stockTransfers,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
  }),
});

export const {
  useStockTransfersQuery,
  useStockTransferQuery,
  useCreateStockTransferMutation,
  useDispatchStockTransferMutation,
  useReceiveStockTransferMutation,
  useCancelStockTransferMutation,
  useDeleteStockTransferMutation,
} = stockTransfersQuery;