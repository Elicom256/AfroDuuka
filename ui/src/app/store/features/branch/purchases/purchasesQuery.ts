import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const purchasesQuery = createApi({
  reducerPath: 'purchasesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/purchases/branch-purchases`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PurchasesAPI'],
  endpoints: (builder) => ({
    purchases: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['PurchasesAPI'],
    }),
    purchase: builder.query<any, number>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['PurchasesAPI'],
    }),

    purchaseAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/analytics?period=last_7_days',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['PurchasesAPI'],
    }),
    addPurchase: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['PurchasesAPI'],
    }),
    updatePurchase: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['PurchasesAPI'],
    }),
    deletePurchase: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['PurchasesAPI'],
    }),
    receivePurchase: builder.mutation<
      any,
      { id: number | string; items: { purchase_item_id: number; quantity: number }[] }
    >({
      query: ({ id, items }) => ({
        url: `/${id}/receive`,
        method: 'POST',
        body: { items },
      }),
      invalidatesTags: ['PurchasesAPI'],
    }),
  }),
});

export const {
  usePurchasesQuery,
  usePurchaseQuery,
  useAddPurchaseMutation,
  useUpdatePurchaseMutation,
  useDeletePurchaseMutation,
  usePurchaseAnalyticsQuery,
  useReceivePurchaseMutation,
} = purchasesQuery;
