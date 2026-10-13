import { baseApi } from '@/app/store/baseApi';

export const purchaseOrdersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    purchaseOrders: builder.query<any, void>({
      query: () => ({ url: '/purchase-orders/', method: 'GET' }),
      providesTags: ['PurchaseOrdersAPI'],
    }),
    purchaseOrder: builder.query<any, string>({
      query: (id) => ({ url: `/purchase-orders/${id}`, method: 'GET' }),
      providesTags: ['PurchaseOrdersAPI'],
    }),
    createPurchaseOrder: builder.mutation<any, any>({
      query: (body) => ({ url: '/purchase-orders/', method: 'POST', body }),
      invalidatesTags: ['PurchaseOrdersAPI'],
    }),
    updatePurchaseOrder: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/purchase-orders/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PurchaseOrdersAPI'],
    }),
    deletePurchaseOrder: builder.mutation<any, string>({
      query: (id) => ({ url: `/purchase-orders/${id}`, method: 'DELETE' }),
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
