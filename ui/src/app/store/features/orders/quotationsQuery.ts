import { baseApi } from '@/app/store/baseApi';

export const quotationsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    quotations: builder.query<any, { status?: string } | void>({
      query: (params) => ({ url: '/quotations/', method: 'GET', params: params ?? {} }),
      providesTags: ['QuotationsAPI'],
    }),
    quotation: builder.query<any, string>({
      query: (id) => ({ url: `/quotations/${id}`, method: 'GET' }),
      providesTags: ['QuotationsAPI'],
    }),
    createQuotation: builder.mutation<any, any>({
      query: (body) => ({ url: '/quotations/', method: 'POST', body }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    updateQuotation: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/quotations/${id}`, method: 'PUT', body }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    deleteQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/quotations/${id}`, method: 'DELETE' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    sendQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/quotations/${id}/send`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    acceptQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/quotations/${id}/accept`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    cancelQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/quotations/${id}/cancel`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    quotationPdf: builder.query<any, string>({
      query: (id) => ({ url: `/quotations/${id}/pdf`, method: 'GET' }),
    }),
  }),
});

export const {
  useQuotationsQuery,
  useQuotationQuery,
  useCreateQuotationMutation,
  useUpdateQuotationMutation,
  useDeleteQuotationMutation,
  useSendQuotationMutation,
  useAcceptQuotationMutation,
  useCancelQuotationMutation,
  useQuotationPdfQuery,
} = quotationsQuery;
