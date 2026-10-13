import { baseApi } from '@/app/store/baseApi';

export const branchWorkersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchWorkers: builder.query<any, void>({
      query: () => ({
        url: '/users/workers/',
        method: 'GET',
      }),
      providesTags: ['BranchWorkersAPI'],
    }),
    branchWorker: builder.query<any, string>({
      query: (id) => ({
        url: `/users/workers/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchWorkersAPI'],
    }),
    addBranchWorker: builder.mutation<any, any>({
      query: (body) => ({
        url: '/users/workers/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['BranchWorkersAPI'],
    }),
    updateBranchWorker: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/users/workers/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['BranchWorkersAPI'],
    }),
    deleteBranchWorker: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/users/workers/${id}`,
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
