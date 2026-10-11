import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const purchaseReturnsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    purchaseReturns: builder.query<any, void>({
      query: () => ({ url: '/returns/purchase-returns/', method: 'GET' }),
      providesTags: withDashboard(apiTags.purchaseReturns),
    }),
    purchaseReturn: builder.query<any, string>({
      query: (id) => ({ url: `/returns/purchase-returns/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.purchaseReturns),
    }),
    // A purchase return reverses stock and what was owed/paid, so it refreshes
    // the money and stock read-models too.
    addPurchaseReturn: builder.mutation<any, any>({
      query: (body) => ({ url: '/returns/purchase-returns/', method: 'POST', body }),
      invalidatesTags: withDashboard(
        apiTags.purchaseReturns,
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

export const { usePurchaseReturnsQuery, usePurchaseReturnQuery, useAddPurchaseReturnMutation } =
  purchaseReturnsQuery;