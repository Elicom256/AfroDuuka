import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const financeQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // Dashboard summary
    getFinanceDashboard: builder.query<any, { branch_id?: string } | void>({
      query: (params) => ({ url: '/finances/dashboard', method: 'GET', params: params ?? undefined }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Paginated transactions with filters
    getFinanceTransactions: builder.query<
      any,
      { page?: number; type?: string; category?: string; date_from?: string; date_to?: string; branch_id?: string; search?: string; per_page?: number }
    >({
      query: (params) => ({ url: '/finances/transactions', method: 'GET', params }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Single transaction
    getFinanceTransaction: builder.query<any, string>({
      query: (id) => ({ url: `/finances/transactions/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // A manual adjustment moves the ledger and the cash balance together.
    createFinanceAdjustment: builder.mutation<any, any>({
      query: (body) => ({ url: '/finances/adjustments', method: 'POST', body }),
      invalidatesTags: withDashboard(apiTags.finance, apiTags.cashflow),
    }),
    // Revenue report
    getRevenueReport: builder.query<any, { start_date?: string; end_date?: string; group_by?: string; branch_id?: string }>({
      query: (params) => ({ url: '/finances/reports/revenue', method: 'GET', params }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Expense report
    getExpenseReport: builder.query<any, { start_date?: string; end_date?: string; branch_id?: string }>({
      query: (params) => ({ url: '/finances/reports/expenses', method: 'GET', params }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Income summary
    getIncomeSummary: builder.query<any, { year?: string; branch_id?: string }>({
      query: (params) => ({ url: '/finances/reports/income-summary', method: 'GET', params }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Branch financial statement
    getBranchStatement: builder.query<any, { branchId: string; start_date?: string; end_date?: string }>({
      query: ({ branchId, ...params }) => ({
        url: `/finances/reports/branch-statement/${branchId}`,
        method: 'GET',
        params,
      }),
      providesTags: withDashboard(apiTags.finance),
    }),
    // Business financial statement
    getBusinessStatement: builder.query<any, { start_date?: string; end_date?: string }>({
      query: (params) => ({ url: '/finances/reports/business-statement', method: 'GET', params }),
      providesTags: withDashboard(apiTags.finance),
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