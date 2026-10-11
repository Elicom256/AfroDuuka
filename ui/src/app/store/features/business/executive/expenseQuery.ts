import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const expenseQuery = baseApi.injectEndpoints({
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
        return { url: `/expenses/branch-expenses/?${qs}`, method: 'GET' };
      },
      providesTags: withDashboard(apiTags.expenses),
    }),
    getExpense: builder.query<any, string>({
      query: (id) => ({ url: `/expenses/branch-expenses/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.expenses),
    }),
    // Each write also moves the ledger and the cash balance, so the finance and
    // cash-flow read models are invalidated with the expense list.
    addExpense: builder.mutation<any, any>({
      query: (body) => ({ url: '/expenses/branch-expenses/', method: 'POST', body }),
      invalidatesTags: withDashboard(apiTags.expenses, apiTags.finance, apiTags.cashflow, apiTags.reports),
    }),
    updateExpense: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/expenses/branch-expenses/${id}`, method: 'PUT', body }),
      invalidatesTags: withDashboard(apiTags.expenses, apiTags.finance, apiTags.cashflow, apiTags.reports),
    }),
    deleteExpense: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/expenses/branch-expenses/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(apiTags.expenses, apiTags.finance, apiTags.cashflow, apiTags.reports),
    }),
    approveExpense: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/expenses/branch-expenses/${id}/approve`, method: 'POST' }),
      invalidatesTags: withDashboard(apiTags.expenses, apiTags.finance, apiTags.cashflow, apiTags.reports),
    }),
    getMonthlyExpenseSummary: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.year) searchParams.set('year', params.year);
        return { url: `/expenses/branch-expenses/monthly-summary?${searchParams.toString()}`, method: 'GET' };
      },
      providesTags: withDashboard(apiTags.expenses),
    }),
    getExpenseTotalsByCategory: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.year) searchParams.set('year', params.year);
        return { url: `/expenses/branch-expenses/totals-by-category?${searchParams.toString()}`, method: 'GET' };
      },
      providesTags: withDashboard(apiTags.expenses),
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