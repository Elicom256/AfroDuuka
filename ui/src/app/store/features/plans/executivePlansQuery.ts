import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const executivePlansQuery = createApi({
  reducerPath: 'executivePlansPath',
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
  tagTypes: ['ExecutivePlansAPI'],
  endpoints: (builder) => ({
    getExecutivePlans: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ExecutivePlansAPI'],
    }),
    createPlan: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ExecutivePlansAPI'],
    }),
    getPlan: builder.query<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ExecutivePlansAPI'],
    }),
    updatePlan: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExecutivePlansAPI'],
    }),
    deletePlan: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ExecutivePlansAPI'],
    }),
  }),
});

export const {
  useGetExecutivePlansQuery,
  useCreatePlanMutation,
  useGetPlanQuery,
  useUpdatePlanMutation,
  useDeletePlanMutation,
} = executivePlansQuery;
