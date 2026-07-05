import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const subscriptionPaymentsQuery = createApi({
  reducerPath: 'subscriptionPaymentsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/subscription-payments`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SubscriptionPaymentsAPI'],
  endpoints: (builder) => ({
    getSubscriptionPayments: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['SubscriptionPaymentsAPI'],
    }),
    createSubscriptionPayment: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['SubscriptionPaymentsAPI'],
    }),
    updateSubscriptionPayment: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SubscriptionPaymentsAPI'],
    }),
    getSubscriptionPayment: builder.query<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
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
