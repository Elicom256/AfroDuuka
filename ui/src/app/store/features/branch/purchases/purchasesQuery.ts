import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { apiTags, withDashboard } from '@/app/store/apiTags';

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
  tagTypes: [apiTags.purchases, apiTags.dashboard],
  endpoints: (builder) => ({
    purchases: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),
    purchase: builder.query<any, number>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),

    purchaseAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/analytics?period=last_7_days',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),
    addPurchase: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.purchases),
    }),
    updatePurchase: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.purchases),
    }),
    deletePurchase: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: withDashboard(apiTags.purchases),
    }),
    receivePurchase: builder.mutation<
      any,
      {
        id: number | string;
        items: Array<{
          purchase_item_id: number;
          quantity: number;
          cost_price?: number;
          selling_price?: number | null;
        }>;
        note?: string | null;
      }
    >({
      query: ({ id, items, note }) => ({
        url: `/${id}/receive`,
        method: 'POST',
        body: { items, note },
      }),
      invalidatesTags: withDashboard(apiTags.purchases),
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
