import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const bsbranchProductsQuery = createApi({
  reducerPath: 'branchProductsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products/business-branch-products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchProductsAPI'],
  endpoints: (builder) => ({
    // get products
    branchProducts: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),
    // get one product by id
    branchProduct: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),
   
  }),
});

export const { useBranchProductsQuery, useBranchProductQuery } = bsbranchProductsQuery;
