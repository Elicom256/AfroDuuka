import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const salesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    sales: builder.query<any, void>({
      query: () => ({
        url: '/sales/branch-sales/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    sale: builder.query<any, string>({
      query: (id) => ({
        url: `/sales/branch-sales/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    getSalesAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/sales/branch-sales/analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    getReturnAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/sales/branch-sales/return-analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    // A sale moves stock, cash and revenue, so it must also refresh product,
    // inventory, finance, cash-flow and reporting read-models — which is only
    // possible because they now live in the same API slice as this mutation.
    addSale: builder.mutation<any, any>({
      query: (body) => ({
        url: '/sales/branch-sales/',
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(
        apiTags.sales,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
    }),
    updateSale: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/sales/branch-sales/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(
        apiTags.sales,
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
  useSalesQuery,
  useSaleQuery,
  useAddSaleMutation,
  useUpdateSaleMutation,
  useGetSalesAnalyticsQuery,
  useGetReturnAnalyticsQuery,
} = salesQuery;