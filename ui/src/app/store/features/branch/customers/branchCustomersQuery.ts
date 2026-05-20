import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchCustomersQuery = createApi({
  reducerPath: 'branchCustomersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/customers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchCustomersAPI'],
  endpoints: (builder) => ({
    branchCustomers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchCustomersAPI'],
    }),
    branchCustomer: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchCustomersAPI'],
    }),
  }),
});

export const { useBranchCustomersQuery, useBranchCustomerQuery } = branchCustomersQuery;
