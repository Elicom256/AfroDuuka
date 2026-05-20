import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchMessagesQuery = createApi({
  reducerPath: 'branchMessagesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/messages`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchMessagesAPI'],
  endpoints: (builder) => ({
    branchMessages: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchMessagesAPI'],
    }),
  }),
});

export const { useBranchMessagesQuery } = branchMessagesQuery;
