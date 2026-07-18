import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const purchaseOrdersQuery = createApi({
  reducerPath: 'purchaseOrdersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/orders/purchase-orders`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PurchaseOrdersAPI'],
  endpoints: (builder) => ({
    purchaseOrders: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PurchaseOrdersAPI'],
    }),
    purchaseOrder: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['PurchaseOrdersAPI'],
    }),
    createPurchaseOrder: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['PurchaseOrdersAPI'],
    }),
    updatePurchaseOrder: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PurchaseOrdersAPI'],
    }),
    deletePurchaseOrder: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['PurchaseOrdersAPI'],
    }),
  }),
});

export const {
  usePurchaseOrdersQuery,
  usePurchaseOrderQuery,
  useCreatePurchaseOrderMutation,
  useUpdatePurchaseOrderMutation,
  useDeletePurchaseOrderMutation,
} = purchaseOrdersQuery;
