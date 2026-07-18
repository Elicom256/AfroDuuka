import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const posQuery = createApi({
  reducerPath: 'posPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/pos`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PosAPI', 'HeldSales'],
  endpoints: (builder) => ({
    searchProducts: builder.query<any, string>({
      query: (q) => ({
        url: `/products/search`,
        method: 'GET',
        params: { q },
      }),
    }),
    searchCustomers: builder.query<any, string>({
      query: (q) => ({
        url: `/customers/search`,
        method: 'GET',
        params: { q },
      }),
    }),
    validateCart: builder.mutation<any, any>({
      query: (body) => ({
        url: '/cart/validate',
        method: 'POST',
        body,
      }),
    }),
    checkout: builder.mutation<any, any>({
      query: (body) => ({
        url: '/checkout',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['PosAPI', 'HeldSales'],
    }),
    holdSale: builder.mutation<any, any>({
      query: (body) => ({
        url: '/sales/hold',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['HeldSales'],
    }),
    getHeldSales: builder.query<any, void>({
      query: () => ({
        url: '/sales/held',
        method: 'GET',
      }),
      providesTags: ['HeldSales'],
    }),
    resumeHeldSale: builder.query<any, number>({
      query: (id) => ({
        url: `/sales/held/${id}`,
        method: 'GET',
      }),
      providesTags: ['HeldSales'],
    }),
    deleteHeldSale: builder.mutation<any, number>({
      query: (id) => ({
        url: `/sales/held/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['HeldSales'],
    }),
  }),
});

export const {
  useLazySearchProductsQuery,
  useLazySearchCustomersQuery,
  useValidateCartMutation,
  useCheckoutMutation,
  useHoldSaleMutation,
  useGetHeldSalesQuery,
  useLazyResumeHeldSaleQuery,
  useDeleteHeldSaleMutation,
} = posQuery;