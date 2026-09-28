import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const paymentGatewaysQuery = createApi({
  reducerPath: 'paymentGatewaysPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/payment-gateways`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['PaymentGatewaysAPI'],
  endpoints: (builder) => ({
    paymentGateways: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PaymentGatewaysAPI'],
    }),
    paymentGateway: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['PaymentGatewaysAPI'],
    }),
    createPaymentGateway: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['PaymentGatewaysAPI'],
    }),
    updatePaymentGateway: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PaymentGatewaysAPI'],
    }),
    deletePaymentGateway: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['PaymentGatewaysAPI'],
    }),
  }),
});

export const {
  usePaymentGatewaysQuery,
  usePaymentGatewayQuery,
  useCreatePaymentGatewayMutation,
  useUpdatePaymentGatewayMutation,
  useDeletePaymentGatewayMutation,
} = paymentGatewaysQuery;
