import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const taxInvoicesQuery = createApi({
  reducerPath: 'taxInvoicesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/tax-invoices`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['TaxInvoicesAPI'],
  endpoints: (builder) => ({
    taxInvoices: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['TaxInvoicesAPI'],
    }),
    taxInvoice: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['TaxInvoicesAPI'],
    }),
    createTaxInvoice: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['TaxInvoicesAPI'],
    }),
    submitTaxInvoiceToUra: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}/submit-to-ura`, method: 'POST' }),
      invalidatesTags: ['TaxInvoicesAPI'],
    }),
    deleteTaxInvoice: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TaxInvoicesAPI'],
    }),
  }),
});

export const {
  useTaxInvoicesQuery,
  useTaxInvoiceQuery,
  useCreateTaxInvoiceMutation,
  useSubmitTaxInvoiceToUraMutation,
  useDeleteTaxInvoiceMutation,
} = taxInvoicesQuery;
