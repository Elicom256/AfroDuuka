import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const saleReturnsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    saleReturns: builder.query<any, void>({
      query: () => ({ url: '/returns/sale-returns/', method: 'GET' }),
      providesTags: withDashboard(apiTags.saleReturns),
    }),
    saleReturn: builder.query<any, string>({
      query: (id) => ({ url: `/returns/sale-returns/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.saleReturns),
    }),
    // A sale return reverses stock, cash and revenue. Because this mutation and
    // the sales/products/finance/reporting queries share one API slice, the tags
    // below actually refetch all of them — this is what the manual
    // util.invalidateTags workaround used to approximate.
    addSaleReturn: builder.mutation<any, any>({
      query: (body) => ({ url: '/returns/sale-returns/', method: 'POST', body }),
      invalidatesTags: withDashboard(
        apiTags.saleReturns,
        apiTags.sales,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
    }),
    updateSaleReturn: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/returns/sale-returns/${id}`, method: 'PUT', body }),
      invalidatesTags: withDashboard(
        apiTags.saleReturns,
        apiTags.sales,
        apiTags.products,
        apiTags.inventory,
        apiTags.finance,
        apiTags.cashflow,
        apiTags.reports
      ),
    }),
    deleteSaleReturn: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/returns/sale-returns/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(
        apiTags.saleReturns,
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
  useSaleReturnsQuery,
  useSaleReturnQuery,
  useAddSaleReturnMutation,
  useUpdateSaleReturnMutation,
  useDeleteSaleReturnMutation,
} = saleReturnsQuery;