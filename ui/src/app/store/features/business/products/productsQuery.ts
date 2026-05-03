import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productsQuery = createApi({
  reducerPath: 'productsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
  }),
  tagTypes: ['ProductsAPI'],
  endpoints: (builder) => ({
    getProducts: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
  }),
});

export const { useGetProductsQuery } = productsQuery;
