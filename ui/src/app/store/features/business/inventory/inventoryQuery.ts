import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const inventoryQuery = createApi({
  reducerPath: 'inventoryPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
  }),
  tagTypes: [apiTags.inventory, apiTags.dashboard],
  endpoints: (builder) => ({
    getInventory: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.inventory),
    }),
  }),
});

export const { useGetInventoryQuery } = inventoryQuery;
