import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const stockTransfersQuery = createApi({
  reducerPath: 'stockTransfersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/stock-transfers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['StockTransfersAPI'],
  endpoints: (builder) => ({
    stockTransfers: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['StockTransfersAPI'],
    }),
    stockTransfer: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['StockTransfersAPI'],
    }),
    createStockTransfer: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['StockTransfersAPI'],
    }),
    dispatchStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/dispatch`, method: 'POST' }),
      invalidatesTags: ['StockTransfersAPI'],
    }),
    receiveStockTransfer: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/${id}/receive`, method: 'POST', body }),
      invalidatesTags: ['StockTransfersAPI'],
    }),
    cancelStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/cancel`, method: 'POST' }),
      invalidatesTags: ['StockTransfersAPI'],
    }),
    deleteStockTransfer: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['StockTransfersAPI'],
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
