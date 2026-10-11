import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { apiTags, withDashboard } from '@/app/store/apiTags';

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
  tagTypes: [apiTags.sales, apiTags.dashboard],
  endpoints: (builder) => ({
    sales: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    sale: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    getSalesAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    getReturnAnalytics: builder.query({
      query: (period = 'last_7_days') => ({
        url: '/return-analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.sales),
    }),
    addSale: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.sales),
    }),
    updateSale: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.sales),
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
