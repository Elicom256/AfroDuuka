import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { salesQuery } from '../sales/salesQuery';
import { financeQuery } from '../../finance/financeQuery';
import { cashFlowQuery } from '../../business/executive/cashFlowQuery';

export const saleReturnsQuery = createApi({
  reducerPath: 'saleReturnsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/returns/sale-returns`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SaleReturnsAPI'],
  endpoints: (builder) => ({
    saleReturns: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['SaleReturnsAPI'],
    }),
    saleReturn: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['SaleReturnsAPI'],
    }),
    addSaleReturn: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['SaleReturnsAPI'],
      async onQueryStarted(_arg, { dispatch, queryFulfilled }) {
        await queryFulfilled;
        dispatch(invalidateRevenueSlices(dispatch));
      },
    }),
    updateSaleReturn: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SaleReturnsAPI'],
      async onQueryStarted(_arg, { dispatch, queryFulfilled }) {
        await queryFulfilled;
        dispatch(invalidateRevenueSlices(dispatch));
      },
    }),
    deleteSaleReturn: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['SaleReturnsAPI'],
      async onQueryStarted(_arg, { dispatch, queryFulfilled }) {
        await queryFulfilled;
        dispatch(invalidateRevenueSlices(dispatch));
      },
    }),
  }),
});

/**
 * A sale return moves money and stock, so every revenue-derived slice must refetch.
 *
 * RTK Query tags are scoped to the slice that declares them, so listing 'SalesAPI'
 * in this slice's invalidatesTags is inert -- that tag does not exist here. The
 * dashboard widgets read the sales and finance slices, and those keep serving the
 * pre-return figures from cache while the cashflow page shows the new numbers.
 * Dispatching each slice's own invalidateTags is what actually busts the cache.
 */
function invalidateRevenueSlices(dispatch: any) {
  return Promise.all([
    dispatch(salesQuery.util.invalidateTags(['SalesAPI'])),
    dispatch(financeQuery.util.invalidateTags(['FinanceAPI'])),
    dispatch(cashFlowQuery.util.invalidateTags(['CashFlowAPI'])),
  ]);
}

export const {
  useSaleReturnsQuery,
  useSaleReturnQuery,
  useAddSaleReturnMutation,
  useUpdateSaleReturnMutation,
  useDeleteSaleReturnMutation,
} = saleReturnsQuery;
