import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const salesQuery = createApi({
  reducerPath: 'salesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/sales/branch-sales`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SalesAPI'],
  endpoints: (builder) => ({
    sales: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
    sale: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
    getSalesAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['SalesAPI'],
    }),
    getReturnAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/return-analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['SalesAPI', 'SaleReturnsAPI'],
    }),
    addSale: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['SalesAPI'],
    }),
    updateSale: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['SalesAPI'],
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
