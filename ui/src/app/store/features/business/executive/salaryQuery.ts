import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const salaryQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    salaries: builder.query<any, void>({
      query: () => ({ url: '/dashboard/salaries/', method: 'GET' }),
      providesTags: withDashboard(apiTags.salary),
    }),

    salary: builder.query<any, string>({
      query: (id) => ({ url: `/dashboard/salaries/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.salary),
    }),

    // Payroll lands in the ledger, so the finance and cash-flow read models move too.
    storeSalary: builder.mutation<any, any>({
      query: (body) => ({ url: '/dashboard/salaries/', method: 'POST', body }),
      invalidatesTags: withDashboard(apiTags.salary, apiTags.finance, apiTags.cashflow),
    }),

    updateSalary: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/dashboard/salaries/${id}`, method: 'PUT', body }),
      invalidatesTags: withDashboard(apiTags.salary, apiTags.finance, apiTags.cashflow),
    }),

    deleteSalary: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/dashboard/salaries/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(apiTags.salary, apiTags.finance, apiTags.cashflow),
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