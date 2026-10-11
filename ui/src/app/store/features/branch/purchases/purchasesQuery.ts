import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const purchasesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    purchases: builder.query<any, void>({
      query: () => ({
        url: '/purchases/branch-purchases/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),
    purchase: builder.query<any, number>({
      query: (id) => ({
        url: `/purchases/branch-purchases/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),

    purchaseAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/purchases/branch-purchases/analytics?period=last_7_days',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.purchases),
    }),
    // Receiving (and editing) a purchase changes stock and cost, and posts to the
    // money read-models, so all of those tags are invalidated together.
    addPurchase: builder.mutation<any, any>({
      query: (body) => ({
        url: '/purchases/branch-purchases/',
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(
        apiTags.purchases,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
    }),
    updatePurchase: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/purchases/branch-purchases/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(
        apiTags.purchases,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
    }),
    deletePurchase: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/purchases/branch-purchases/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: withDashboard(
        apiTags.purchases,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
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
        url: `/purchases/branch-purchases/${id}/receive`,
        method: 'POST',
        body: { items, note },
      }),
      invalidatesTags: withDashboard(
        apiTags.purchases,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
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