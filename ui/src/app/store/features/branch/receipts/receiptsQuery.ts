import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const receiptsQuery = createApi({
  reducerPath: 'receiptsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/receipts`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ReceiptsAPI'],
  endpoints: (builder) => ({
    receipts: builder.query<any, Record<string, any> >({
      query: (params) => ({
        url: '/',
        method: 'GET',
        params,
      }),
      providesTags: ['ReceiptsAPI'],
    }),
    receipt: builder.query<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['ReceiptsAPI'],
    }),
    downloadReceiptPdf: builder.mutation<{ pdf: string; filename: string }, number>({
      queryFn: async (id) => {
        const baseUrl = import.meta.env.VITE_BASE_URL || 'http://localhost/api';
        const token = localStorage.getItem('token');
        const response = await fetch(`${baseUrl}/receipts/${id}/pdf`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        });
        if (!response.ok) {
          return { error: { status: response.status, data: await response.text() } };
        }
        const data = await response.json();
        return { data };
      },
    }),
  }),
});

export const {
  useReceiptsQuery,
  useReceiptQuery,
  useDownloadReceiptPdfMutation,
} = receiptsQuery;
