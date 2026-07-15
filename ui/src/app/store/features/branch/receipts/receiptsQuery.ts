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
    receipts: builder.query<any, Record<string, any> | void>({
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
  }),
});

export const {
  useReceiptsQuery,
  useReceiptQuery,
} = receiptsQuery;
