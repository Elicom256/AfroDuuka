import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productAuditQuery = createApi({
  reducerPath: 'productAuditPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/product-audits`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ProductAuditAPI'],
  endpoints: (builder) => ({
    getProductAudits: builder.query<any, any>({
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
      providesTags: ['ProductAuditAPI'],
    }),
    getProductAudit: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ProductAuditAPI'],
    }),
    addProductAudit: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ProductAuditAPI'],
    }),
    updateProductAudit: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ProductAuditAPI'],
    }),
    deleteProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ProductAuditAPI'],
    }),
    approveProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}/approve`, method: 'POST' }),
      invalidatesTags: ['ProductAuditAPI'],
    }),
    cancelProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/${id}/cancel`, method: 'POST' }),
      invalidatesTags: ['ProductAuditAPI'],
    }),
    getProductAuditReport: builder.query<any, string>({
      query: (id) => ({ url: `/${id}/report`, method: 'GET' }),
    }),
  }),
});

export const {
  useGetProductAuditsQuery,
  useGetProductAuditQuery,
  useAddProductAuditMutation,
  useUpdateProductAuditMutation,
  useDeleteProductAuditMutation,
  useApproveProductAuditMutation,
  useCancelProductAuditMutation,
  useGetProductAuditReportQuery,
} = productAuditQuery;
