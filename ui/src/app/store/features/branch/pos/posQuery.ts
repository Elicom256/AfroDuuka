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
  tagTypes: ['PosAPI', 'HeldCarts'],
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
      invalidatesTags: ['PosAPI', 'HeldCarts'],
    }),
    holdCart: builder.mutation<any, any>({
      query: (body) => ({
        url: '/cart/hold',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['HeldCarts'],
    }),
    getHeldCarts: builder.query<any, void>({
      query: () => ({
        url: '/cart/held',
        method: 'GET',
      }),
      providesTags: ['HeldCarts'],
    }),
    resumeCart: builder.query<any, number>({
      query: (id) => ({
        url: `/cart/resume/${id}`,
        method: 'GET',
      }),
      providesTags: ['HeldCarts'],
    }),
    deleteHeldCart: builder.mutation<any, number>({
      query: (id) => ({
        url: `/cart/held/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['HeldCarts'],
    }),
  }),
});

export const {
  useLazySearchProductsQuery,
  useLazySearchCustomersQuery,
  useValidateCartMutation,
  useCheckoutMutation,
  useHoldCartMutation,
  useGetHeldCartsQuery,
  useLazyResumeCartQuery,
  useDeleteHeldCartMutation,
} = posQuery;
