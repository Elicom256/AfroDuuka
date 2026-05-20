import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchSuppliersQuery = createApi({
  reducerPath: 'branchSuppliersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/suppliers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchSuppliersAPI'],
  endpoints: (builder) => ({
    branchSuppliers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchSuppliersAPI'],
    }),
    branchSupplier: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchSuppliersAPI'],
    }),
  }),
});

export const { useBranchSuppliersQuery, useBranchSupplierQuery } = branchSuppliersQuery;
