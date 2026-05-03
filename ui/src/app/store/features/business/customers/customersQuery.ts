import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const customersQuery = createApi({
  reducerPath: 'customersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/customers`,
  }),
  tagTypes: ['CustomersAPI'],
  endpoints: (builder) => ({
    getCustomers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['CustomersAPI'],
    }),
  }),
});

export const { useGetCustomersQuery } = customersQuery;
