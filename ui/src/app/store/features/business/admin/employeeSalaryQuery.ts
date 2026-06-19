import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const employeeSalaryQuery = createApi({
  reducerPath: 'adminEmployeeRemunerationPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/employee-salary`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['EmployeeSalary'],
  endpoints: (builder) => ({
    employeeSalaries: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['EmployeeSalary'],
    }),

    employeeSalary: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['EmployeeSalary'],
    }),

    storeEmployeeSalary: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['EmployeeSalary'],
    }),

    updateEmployeeSalary: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['EmployeeSalary'],
    }),
  }),
});

export const {
  useEmployeeSalariesQuery,
  useStoreEmployeeSalaryMutation,
  useUpdateEmployeeSalaryMutation,
  useEmployeeSalaryQuery,
} = employeeSalaryQuery;
