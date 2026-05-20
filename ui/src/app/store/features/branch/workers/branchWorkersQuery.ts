import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchWorkersQuery = createApi({
  reducerPath: 'branchWorkersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/workers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchWorkersAPI'],
  endpoints: (builder) => ({
    branchWorkers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchWorkersAPI'],
    }),
    branchWorker: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchWorkersAPI'],
    }),
    addBranchWorker: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['BranchWorkersAPI'],
    }),
    updateBranchWorker: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['BranchWorkersAPI'],
    }),
    deleteBranchWorker: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['BranchWorkersAPI'],
    }),
  }),
});

export const {
  useBranchWorkersQuery,
  useBranchWorkerQuery,
  useAddBranchWorkerMutation,
  useUpdateBranchWorkerMutation,
  useDeleteBranchWorkerMutation,
} = branchWorkersQuery;
