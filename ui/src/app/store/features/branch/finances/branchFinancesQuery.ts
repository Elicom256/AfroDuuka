import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchFinancesQuery = createApi({
  reducerPath: 'branchFinancesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/finances`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchFinancesAPI'],
  endpoints: (builder) => ({
    branchFinances: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchFinancesAPI'],
    }),
  }),
});

export const { useBranchFinancesQuery } = branchFinancesQuery;
