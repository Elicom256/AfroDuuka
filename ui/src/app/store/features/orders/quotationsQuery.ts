import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const quotationsQuery = createApi({
  reducerPath: 'quotationsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/quotations`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['QuotationsAPI'],
  endpoints: (builder) => ({
    quotations: builder.query<any, { status?: string } | void>({
      query: (params) => ({ url: '/', method: 'GET', params: params ?? {} }),
      providesTags: ['QuotationsAPI'],
    }),
    quotation: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['QuotationsAPI'],
    }),
    createQuotation: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    updateQuotation: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    deleteQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    sendQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/send`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    acceptQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/accept`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    cancelQuotation: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/cancel`, method: 'POST' }),
      invalidatesTags: ['QuotationsAPI'],
    }),
    quotationPdf: builder.query<any, string>({
      query: (id) => ({ url: `/${id}/pdf`, method: 'GET' }),
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