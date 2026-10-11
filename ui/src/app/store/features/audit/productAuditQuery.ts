import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const productAuditQuery = baseApi.injectEndpoints({
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
        return { url: `/product-audits/?${qs}`, method: 'GET' };
      },
      providesTags: withDashboard(apiTags.productAudit),
    }),
    getProductAudit: builder.query<any, string>({
      query: (id) => ({ url: `/product-audits/${id}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.productAudit),
    }),
    // Approving/creating an audit reconciles counted stock against the system, so
    // product and inventory figures move with it.
    addProductAudit: builder.mutation<any, any>({
      query: (body) => ({ url: '/product-audits/', method: 'POST', body }),
      invalidatesTags: withDashboard(
        apiTags.productAudit,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    updateProductAudit: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/product-audits/${id}`, method: 'PUT', body }),
      invalidatesTags: withDashboard(
        apiTags.productAudit,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    deleteProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/product-audits/${id}`, method: 'DELETE' }),
      invalidatesTags: withDashboard(apiTags.productAudit),
    }),
    approveProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/product-audits/${id}/approve`, method: 'POST' }),
      invalidatesTags: withDashboard(
        apiTags.productAudit,
        apiTags.products,
        apiTags.inventory,
        apiTags.reports
      ),
    }),
    cancelProductAudit: builder.mutation<any, string | number>({
      query: (id) => ({ url: `/product-audits/${id}/cancel`, method: 'POST' }),
      invalidatesTags: withDashboard(apiTags.productAudit),
    }),
    getProductAuditReport: builder.query<any, string>({
      query: (id) => ({ url: `/product-audits/${id}/report`, method: 'GET' }),
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