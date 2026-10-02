import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const executiveEmployeeRemunerationQuery = createApi({
  reducerPath: 'executiveEmployeeRemunerationPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/dashboard/employee-remuneration`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ExecutiveRemunerationAPI'],
  endpoints: (builder) => ({
    getExecutiveEmployeeRemuneration: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ExecutiveRemunerationAPI'],
    }),

    storeExecutiveEmployeeRemuneration: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ExecutiveRemunerationAPI'],
    }),

    updateExecutiveEmployeeRemuneration: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExecutiveRemunerationAPI'],
    }),
  }),
});

export const {
  useGetExecutiveEmployeeRemunerationQuery,
  useStoreExecutiveEmployeeRemunerationMutation,
  useUpdateExecutiveEmployeeRemunerationMutation,
} = executiveEmployeeRemunerationQuery;
