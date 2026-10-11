import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const branchesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branches: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/branches/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.branches),
    }),
    branch: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/branches/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.branches),
    }),
    // Branch bookkeeping changes the branch list and the reports keyed on it, but
    // no money or stock numbers.
    addBranch: builder.mutation<any, any>({
      query: (body) => ({
        url: '/dashboard/branches/',
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.branches, apiTags.reports),
    }),
    updateBranch: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/dashboard/branches/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.branches, apiTags.reports),
    }),
    deleteBranch: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/dashboard/branches/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: withDashboard(apiTags.branches, apiTags.reports),
    }),
    // Branch activity is a money read-model, so it shares the tags the money writers
    // invalidate: a sale or purchase must move it.
    branchDynamics: builder.query<any, void>({
      query: () => ({
        url: `/dashboard/branches/dynamics`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.branches, apiTags.cashflow, apiTags.finance, apiTags.reports),
    }),
    // ============== cashflow analytics ===================
    cashFlowAnalytics: builder.query<any, any>({
      query: (period = 'last_7_days') => ({
        url: '/dashboard/branches/cashflow/analytics',
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.branches, apiTags.cashflow, apiTags.finance, apiTags.reports),
    }),
  }),
});

export const {
  useBranchesQuery,
  useBranchQuery,
  useAddBranchMutation,
  useUpdateBranchMutation,
  useDeleteBranchMutation,
  useBranchDynamicsQuery,
  useCashFlowAnalyticsQuery,
} = branchesQuery;