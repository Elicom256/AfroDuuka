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
  }),
});

export const { useGetAdminEmployeeRemunerationQuery } = adminEmployeeRemunerationQuery;
