import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const financeQuery = createApi({
  reducerPath: 'financePath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/finances`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['FinanceAPI'],
  endpoints: (builder) => ({
    // Dashboard summary
    getFinanceDashboard: builder.query<any, { branch_id?: string } | void>({
      query: (params) => ({ url: '/dashboard', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Paginated transactions with filters
    getFinanceTransactions: builder.query<any, { page?: number; type?: string; category?: string; date_from?: string; date_to?: string; branch_id?: string; search?: string; per_page?: number }>({
      query: (params) => ({ url: '/transactions', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Single transaction
    getFinanceTransaction: builder.query<any, string>({
      query: (id) => ({ url: `/transactions/${id}`, method: 'GET' }),
      providesTags: ['FinanceAPI'],
    }),
    // Create manual adjustment
    createFinanceAdjustment: builder.mutation<any, any>({
      query: (body) => ({ url: '/adjustments', method: 'POST', body }),
      invalidatesTags: ['FinanceAPI'],
    }),
    // Revenue report
    getRevenueReport: builder.query<any, { start_date?: string; end_date?: string; group_by?: string; branch_id?: string }>({
      query: (params) => ({ url: '/reports/revenue', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Expense report
    getExpenseReport: builder.query<any, { start_date?: string; end_date?: string; branch_id?: string }>({
      query: (params) => ({ url: '/reports/expenses', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Income summary
    getIncomeSummary: builder.query<any, { year?: string; branch_id?: string }>({
      query: (params) => ({ url: '/reports/income-summary', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Branch financial statement
    getBranchStatement: builder.query<any, { branchId: string; start_date?: string; end_date?: string }>({
      query: ({ branchId, ...params }) => ({ url: `/reports/branch-statement/${branchId}`, method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
    // Business financial statement
    getBusinessStatement: builder.query<any, { start_date?: string; end_date?: string }>({
      query: (params) => ({ url: '/reports/business-statement', method: 'GET', params }),
      providesTags: ['FinanceAPI'],
    }),
  }),
});

export const {
  useGetFinanceDashboardQuery,
  useGetFinanceTransactionsQuery,
  useGetFinanceTransactionQuery,
  useCreateFinanceAdjustmentMutation,
  useGetRevenueReportQuery,
  useGetExpenseReportQuery,
  useGetIncomeSummaryQuery,
  useGetBranchStatementQuery,
  useGetBusinessStatementQuery,
} = financeQuery;
