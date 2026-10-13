import { baseApi } from '@/app/store/baseApi';

export const executiveEmployeeRemunerationQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getExecutiveEmployeeRemuneration: builder.query<any, void>({
      query: () => ({ url: '/dashboard/employee-remuneration/', method: 'GET' }),
      providesTags: ['ExecutiveRemunerationAPI'],
    }),

    storeExecutiveEmployeeRemuneration: builder.mutation<any, any>({
      query: (body) => ({ url: '/dashboard/employee-remuneration/', method: 'POST', body }),
      invalidatesTags: ['ExecutiveRemunerationAPI'],
    }),

    updateExecutiveEmployeeRemuneration: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/dashboard/employee-remuneration/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExecutiveRemunerationAPI'],
    }),
  }),
});

export const {
  useGetExecutiveEmployeeRemunerationQuery,
  useStoreExecutiveEmployeeRemunerationMutation,
  useUpdateExecutiveEmployeeRemunerationMutation,
} = executiveEmployeeRemunerationQuery;
