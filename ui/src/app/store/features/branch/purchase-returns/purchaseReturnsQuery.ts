import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const purchaseReturnsQuery = createApi({
  reducerPath: 'purchaseReturnsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/returns/purchase-returns`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PurchaseReturnsAPI'],
  endpoints: (builder) => ({
    purchaseReturns: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PurchaseReturnsAPI'],
    }),
    purchaseReturn: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['PurchaseReturnsAPI'],
    }),
    addPurchaseReturn: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['PurchaseReturnsAPI'],
    }),
  }),
});

export const { usePurchaseReturnsQuery, usePurchaseReturnQuery, useAddPurchaseReturnMutation } = purchaseReturnsQuery;
