import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const expenseQuery = createApi({
  reducerPath: 'expensePath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/expenses/branch-expenses`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ExpenseAPI'],
  endpoints: (builder) => ({
    getExpenses: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set('page', params.page);
        if (params?.expense_category_id) searchParams.set('expense_category_id', params.expense_category_id);
        if (params?.business_branch_id) searchParams.set('business_branch_id', params.business_branch_id);
        if (params?.status) searchParams.set('status', params.status);
        if (params?.date_from) searchParams.set('date_from', params.date_from);
        if (params?.date_to) searchParams.set('date_to', params.date_to);
        if (params?.search) searchParams.set('search', params.search);
        const qs = searchParams.toString();
        return { url: `/?${qs}`, method: 'GET' };
      },
      providesTags: ['ExpenseAPI'],
    }),
    getExpense: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ExpenseAPI'],
    }),
    addExpense: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ExpenseAPI'],
    }),
    updateExpense: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExpenseAPI'],
    }),
    deleteExpense: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ExpenseAPI'],
    }),
    approveExpense: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}/approve`, method: 'POST' }),
      invalidatesTags: ['ExpenseAPI'],
    }),
    getMonthlyExpenseSummary: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.year) searchParams.set('year', params.year);
        return { url: `/monthly-summary?${searchParams.toString()}`, method: 'GET' };
      },
    }),
    getExpenseTotalsByCategory: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.year) searchParams.set('year', params.year);
        return { url: `/totals-by-category?${searchParams.toString()}`, method: 'GET' };
      },
    }),
  }),
});

export const {
  useGetExpensesQuery,
  useGetExpenseQuery,
  useAddExpenseMutation,
  useUpdateExpenseMutation,
  useDeleteExpenseMutation,
  useApproveExpenseMutation,
  useGetMonthlyExpenseSummaryQuery,
  useGetExpenseTotalsByCategoryQuery,
} = expenseQuery;
