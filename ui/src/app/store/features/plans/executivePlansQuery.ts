import { baseApi } from '@/app/store/baseApi';

export const executivePlansQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getExecutivePlans: builder.query<any, void>({
      query: () => ({ url: '/plans/', method: 'GET' }),
      providesTags: ['ExecutivePlansAPI'],
    }),
    createPlan: builder.mutation<any, any>({
      query: (body) => ({ url: '/plans/', method: 'POST', body }),
      invalidatesTags: ['ExecutivePlansAPI'],
    }),
    getPlan: builder.query<any, number>({
      query: (id) => ({ url: `/plans/${id}`, method: 'GET' }),
      providesTags: ['ExecutivePlansAPI'],
    }),
    updatePlan: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/plans/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExecutivePlansAPI'],
    }),
    deletePlan: builder.mutation<any, number>({
      query: (id) => ({ url: `/plans/${id}`, method: 'DELETE' }),
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
