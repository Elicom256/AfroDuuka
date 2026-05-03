import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const salesQuery = createApi({
  reducerPath: 'salesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/orders`,
  }),
  tagTypes: ['SalesAPI'],
  endpoints: (builder) => ({
    getOrders: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
  }),
});

export const { useGetOrdersQuery } = salesQuery;
