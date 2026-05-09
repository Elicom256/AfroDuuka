import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const salesQuery = createApi({
  reducerPath: 'salesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/orders`,
  }),
  tagTypes: ['SalesAPI'],
  endpoints: (builder) => ({
    orders: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
  }),
});

export const { useOrdersQuery } = salesQuery;
