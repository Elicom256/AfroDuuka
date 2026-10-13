import { baseApi } from '@/app/store/baseApi';

export const expenseCategoriesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getExpenseCategories: builder.query<any, void>({
      query: () => ({ url: '/expenses/expense-categories/', method: 'GET' }),
      providesTags: ['ExpenseCategoriesAPI'],
    }),
    getExpenseCategory: builder.query<any, string>({
      query: (id) => ({ url: `/expenses/expense-categories/${id}`, method: 'GET' }),
      providesTags: ['ExpenseCategoriesAPI'],
    }),
    addExpenseCategory: builder.mutation<any, any>({
      query: (body) => ({ url: '/expenses/expense-categories/', method: 'POST', body }),
      invalidatesTags: ['ExpenseCategoriesAPI'],
    }),
    updateExpenseCategory: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/expenses/expense-categories/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ExpenseCategoriesAPI'],
    }),
    deleteExpenseCategory: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/expenses/expense-categories/${id}`, method: 'DELETE' }),
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
