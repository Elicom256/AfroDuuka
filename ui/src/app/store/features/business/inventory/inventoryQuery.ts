import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const inventoryQuery = createApi({
  reducerPath: 'inventoryPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/inventory`,
  }),
  tagTypes: ['InventoryAPI'],
  endpoints: (builder) => ({
    getInventory: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['InventoryAPI'],
    }),
  }),
});

export const { useGetInventoryQuery } = inventoryQuery;
