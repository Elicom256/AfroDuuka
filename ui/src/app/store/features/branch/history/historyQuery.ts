import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchHistoryQuery = createApi({
  reducerPath: 'branchHistoryPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/history`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchHistoryAPI'],
  endpoints: (builder) => ({
    branchHistory: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchHistoryAPI'],
    }),
  }),
});

export const { useBranchHistoryQuery } = branchHistoryQuery;
