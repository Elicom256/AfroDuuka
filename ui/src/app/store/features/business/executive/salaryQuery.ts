import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const salaryQuery = createApi({
  reducerPath: 'salariesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/dashboard/salaries`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['Salary'],
  endpoints: (builder) => ({
    salaries: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['Salary'],
    }),

    salary: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['Salary'],
    }),

    storeSalary: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['Salary'],
    }),

    updateSalary: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['Salary'],
    }),

    deleteSalary: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['Salary'],
    }),
  }),
});

export const {
  useSalariesQuery,
  useSalaryQuery,
  useStoreSalaryMutation,
  useUpdateSalaryMutation,
  useDeleteSalaryMutation,
} = salaryQuery;
