import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchProductsQuery = createApi({
  reducerPath: 'productsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['ProductsAPI'],
  endpoints: (builder) => ({
    // get products
    branchProducts: builder.query<any, void>({
      query: () => ({
        url: '/business-products',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // get one product by id
    branchProduct: builder.query<any, string>({
      query: (id) => ({
        url: `/business-products/${id}`,
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
  
   
   
  }),
});

export const {
  useBranchProductsQuery,
  useBranchProductQuery,
} = branchProductsQuery;
