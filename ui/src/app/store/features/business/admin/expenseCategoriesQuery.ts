import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const expenseCategoriesQuery = createApi({
  reducerPath: 'expenseCategoriesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/expenses/expense-categories`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ExpenseCategoriesAPI'],
  endpoints: (builder) => ({
    getExpenseCategories: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ExpenseCategoriesAPI'],
    }),
    getExpenseCategory: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ExpenseCategoriesAPI'],
    }),
    addExpenseCategory: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ExpenseCategoriesAPI'],
    }),
    updateExpenseCategory: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExpenseCategoriesAPI'],
    }),
    deleteExpenseCategory: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ExpenseCategoriesAPI'],
    }),
  }),
});

export const {
  useGetExpenseCategoriesQuery,
  useGetExpenseCategoryQuery,
  useAddExpenseCategoryMutation,
  useUpdateExpenseCategoryMutation,
  useDeleteExpenseCategoryMutation,
} = expenseCategoriesQuery;
