import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const financialAuditQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getFinancialAudits: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set('page', params.page);
        if (params?.business_branch_id) searchParams.set('business_branch_id', params.business_branch_id);
        if (params?.status) searchParams.set('status', params.status);
        if (params?.date_from) searchParams.set('date_from', params.date_from);
        if (params?.date_to) searchParams.set('date_to', params.date_to);
        if (params?.search) searchParams.set('search', params.search);
        const qs = searchParams.toString();
        return { url: `/financial-audits/?${qs}`, method: 'GET' };
      },
      providesTags: withDashboard(apiTags.financialAudit),
    }),
    getFinancialAudit: builder.query<any, string>({
      query: (id) => ({ url: `/financial-audits/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.financialAudit),
    }),
    // Creating or approving an audit reconciles the ledger, so the finance and
    // cash-flow read models move with it.
    addFinancialAudit: builder.mutation<any, any>({
      query: (body) => ({ url: '/financial-audits/', method: 'POST', body }),
      invalidatesTags: withDashboard(apiTags.financialAudit, apiTags.finance, apiTags.cashflow),
    }),
    updateFinancialAudit: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/financial-audits/${id}`, method: 'PUT', body }),
      invalidatesTags: withDashboard(apiTags.financialAudit, apiTags.finance, apiTags.cashflow),
    }),
    deleteFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/financial-audits/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(apiTags.financialAudit),
    }),
    approveFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/financial-audits/${id}/approve`, method: 'POST' }),
      invalidatesTags: withDashboard(apiTags.financialAudit, apiTags.finance, apiTags.cashflow),
    }),
    cancelFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/financial-audits/${id}/cancel`, method: 'POST' }),
      invalidatesTags: withDashboard(apiTags.financialAudit),
    }),
    getFinancialAuditReport: builder.query<any, string>({
      query: (id) => ({ url: `/financial-audits/${id}/report`, method: 'GET' }),
      providesTags: withDashboard(apiTags.financialAudit),
    }),
  }),
});

export const {
  useGetFinancialAuditsQuery,
  useGetFinancialAuditQuery,
  useAddFinancialAuditMutation,
  useUpdateFinancialAuditMutation,
  useDeleteFinancialAuditMutation,
  useApproveFinancialAuditMutation,
  useCancelFinancialAuditMutation,
  useGetFinancialAuditReportQuery,
} = financialAuditQuery;