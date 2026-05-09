import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const purchasesQuery = createApi({
  reducerPath: 'purchasesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/purchases`,
  }),
  tagTypes: ['PurchasesAPI'],
  endpoints: (builder) => ({
    purchases: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['PurchasesAPI'],
    }),
  }),
});

export const { usePurchasesQuery } = purchasesQuery;
