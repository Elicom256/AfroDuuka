import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const pricingQuery = createApi({
  reducerPath: 'pricingPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/pricing`,
  }),
  tagTypes: ['PricingAPI'],
  endpoints: (builder) => ({
    getPricings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PricingAPI'],
    }),
  }),
});

export const { useGetPricingsQuery } = pricingQuery;
