import { baseApi } from '@/app/store/baseApi';

export const paymentGatewaysQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    paymentGateways: builder.query<any, void>({
      query: () => ({ url: '/payment-gateways/', method: 'GET' }),
      providesTags: ['PaymentGatewaysAPI'],
    }),
    paymentGateway: builder.query<any, string>({
      query: (id) => ({ url: `/payment-gateways/${id}`, method: 'GET' }),
      providesTags: ['PaymentGatewaysAPI'],
    }),
    createPaymentGateway: builder.mutation<any, any>({
      query: (body) => ({ url: '/payment-gateways/', method: 'POST', body }),
      invalidatesTags: ['PaymentGatewaysAPI'],
    }),
    updatePaymentGateway: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/payment-gateways/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PaymentGatewaysAPI'],
    }),
    deletePaymentGateway: builder.mutation<any, string>({
      query: (id) => ({ url: `/payment-gateways/${id}`, method: 'DELETE' }),
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
