import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchesQuery = createApi({
  reducerPath: 'branchesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/branches`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchesAPI'],
  endpoints: (builder) => ({
    branches: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchesAPI'],
    }),
    branch: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchesAPI'],
    }),
    addBranch: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['BranchesAPI'],
    }),
    updateBranch: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['BranchesAPI'],
    }),
    deleteBranch: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['BranchesAPI'],
    }),
  }),
});

export const {
  useBranchesQuery,
  useBranchQuery,
  useAddBranchMutation,
  useUpdateBranchMutation,
  useDeleteBranchMutation,
} = branchesQuery;
