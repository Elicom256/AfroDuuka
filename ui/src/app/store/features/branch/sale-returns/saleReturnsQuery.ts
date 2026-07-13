import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const saleReturnsQuery = createApi({
  reducerPath: 'saleReturnsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/returns/sale-returns`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SaleReturnsAPI'],
  endpoints: (builder) => ({
    saleReturns: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['SaleReturnsAPI'],
    }),
    saleReturn: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['SaleReturnsAPI'],
    }),
    addSaleReturn: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['SaleReturnsAPI'],
    }),
  }),
});

export const { useSaleReturnsQuery, useSaleReturnQuery, useAddSaleReturnMutation } = saleReturnsQuery;
