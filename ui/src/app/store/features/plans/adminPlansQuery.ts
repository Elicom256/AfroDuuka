import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminPlansQuery = createApi({
  reducerPath: 'adminPlansPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/plans`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminPlansAPI'],
  endpoints: (builder) => ({
    getAdminPlans: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminPlansAPI'],
    }),
    createPlan: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['AdminPlansAPI'],
    }),
    getPlan: builder.query<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['AdminPlansAPI'],
    }),
    updatePlan: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['AdminPlansAPI'],
    }),
    deletePlan: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['AdminPlansAPI'],
    }),
  }),
});

export const {
  useGetAdminPlansQuery,
  useCreatePlanMutation,
  useGetPlanQuery,
  useUpdatePlanMutation,
  useDeletePlanMutation,
} = adminPlansQuery;
