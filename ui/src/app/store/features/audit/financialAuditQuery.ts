import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const financialAuditQuery = createApi({
  reducerPath: 'financialAuditPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/financial-audits`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['FinancialAuditAPI'],
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
        return { url: `/?${qs}`, method: 'GET' };
      },
      providesTags: ['FinancialAuditAPI'],
    }),
    getFinancialAudit: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['FinancialAuditAPI'],
    }),
    addFinancialAudit: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['FinancialAuditAPI'],
    }),
    updateFinancialAudit: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['FinancialAuditAPI'],
    }),
    deleteFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['FinancialAuditAPI'],
    }),
    approveFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}/approve`, method: 'POST' }),
      invalidatesTags: ['FinancialAuditAPI'],
    }),
    cancelFinancialAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}/cancel`, method: 'POST' }),
      invalidatesTags: ['FinancialAuditAPI'],
    }),
    getFinancialAuditReport: builder.query<any, string>({
      query: (id) => ({ url: `/${id}/report`, method: 'GET' }),
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
