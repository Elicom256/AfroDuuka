import { baseApi } from '@/app/store/baseApi';

export const subscriptionPaymentsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getSubscriptionPayments: builder.query<any, void>({
      query: () => ({ url: '/subscription-payments/', method: 'GET' }),
      providesTags: ['SubscriptionPaymentsAPI'],
    }),
    createSubscriptionPayment: builder.mutation<any, any>({
      query: (body) => ({ url: '/subscription-payments/', method: 'POST', body }),
      invalidatesTags: ['SubscriptionPaymentsAPI'],
    }),
    updateSubscriptionPayment: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/subscription-payments/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SubscriptionPaymentsAPI'],
    }),
    getSubscriptionPayment: builder.query<any, number>({
      query: (id) => ({ url: `/subscription-payments/${id}`, method: 'GET' }),
      providesTags: ['SubscriptionPaymentsAPI'],
    }),
  }),
});

export const {
  useGetSubscriptionPaymentsQuery,
  useGetSubscriptionPaymentQuery,
  useCreateSubscriptionPaymentMutation,
  useUpdateSubscriptionPaymentMutation,
} = subscriptionPaymentsQuery;
