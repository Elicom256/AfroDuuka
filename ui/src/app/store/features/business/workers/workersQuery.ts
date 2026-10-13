import { baseApi } from '@/app/store/baseApi';
// here the admin manages business workers
export const workersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getWorkersInfo: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/workers/',
        method: 'GET',
      }),
      providesTags: ['WorkersAPI'],
    }),

    getWorkerInfo: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/workers/${id}`,
        method: 'GET',
      }),
      providesTags: ['WorkersAPI'],
    }),
    // register worker
    registerWorker: builder.mutation<any, any>({
      query: (body) => ({
        url: '/dashboard/workers/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // update worker
    updateWorker: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/dashboard/workers/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // delete worker
    deleteWorker: builder.mutation<any, { userData: any; id: number }>({
      query: (id) => ({
        url: `/dashboard/workers/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
  }),
});
export const {
  useGetWorkersInfoQuery,
  useGetWorkerInfoQuery,
  useDeleteWorkerMutation,
  useRegisterWorkerMutation,
  useUpdateWorkerMutation,
} = workersQuery;
