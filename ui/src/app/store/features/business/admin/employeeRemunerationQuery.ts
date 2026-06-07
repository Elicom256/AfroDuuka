import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminEmployeeRemunerationQuery = createApi({
  reducerPath: 'adminEmployeeRemunerationPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/employee-remuneration`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminRemunerationAPI'],
  endpoints: (builder) => ({
    getAdminEmployeeRemuneration: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminRemunerationAPI'],
    }),

    storeAdminEmployeeRemuneration: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['AdminRemunerationAPI'],
    }),

    updateAdminEmployeeRemuneration: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['AdminRemunerationAPI'],
    }),
  }),
});

export const {
  useGetAdminEmployeeRemunerationQuery,
  useStoreAdminEmployeeRemunerationMutation,
  useUpdateAdminEmployeeRemunerationMutation,
} = adminEmployeeRemunerationQuery;
