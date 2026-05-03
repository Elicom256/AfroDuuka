import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
// here the admin manages business workers
export const workersQuery = createApi({
  reducerPath: 'workerPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/users`,
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
    registerWorker: builder.mutation({
      query: (userData) => ({
        url: '/add-worker',
        method: 'POST',
        body: userData,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // update worker
    updateWorker: builder.mutation<any, { userData: any; id: number }>({
      query: ({ userData, id }) => ({
        url: `/user/${id}`,
        method: 'PATCH',
        body: userData,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
    // delete worker
    deleteWorker: builder.mutation<any, { userData: any; id: number }>({
      query: ({ userData, id }) => ({
        url: `/user/${id}`,
        method: 'PATCH',
        body: userData,
      }),
      invalidatesTags: ['WorkersAPI'],
    }),
  }),
});
export const { useGetWorkersInfoQuery, useDeleteWorkerMutation, useRegisterWorkerMutation, useUpdateWorkerMutation } =
  workersQuery;
