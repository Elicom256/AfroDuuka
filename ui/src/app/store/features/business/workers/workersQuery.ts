import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
// here the admin manages business workers
export const workersQuery = createApi({
  reducerPath: 'workerPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/workers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['WorkersAPI'],
  endpoints: (builder) => ({
    getWorkersInfo: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['WorkersAPI'],
    }),
    // register worker
    registerWorker: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // update worker
    updateWorker: builder.mutation<any, { userData: any; id: string }>({
      query: ({ userData, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body: userData,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // delete worker
    deleteWorker: builder.mutation<any, { userData: any; id: number }>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
  }),
});
export const { useGetWorkersInfoQuery, useDeleteWorkerMutation, useRegisterWorkerMutation, useUpdateWorkerMutation } =
  workersQuery;
