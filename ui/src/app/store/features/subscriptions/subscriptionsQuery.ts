import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const subscriptionsQuery = createApi({
  reducerPath: 'subscriptionsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/subscriptions/business-subscriptions`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SubscriptionsAPI'],
  endpoints: (builder) => ({
    getSubscriptions: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['SubscriptionsAPI'],
    }),
    createSubscription: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    updateSubscription: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    deleteSubscription: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    getSubscription: builder.query<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['SubscriptionsAPI'],
    }),
  }),
});

export const {
  useGetSubscriptionsQuery,
  useGetSubscriptionQuery,
  useCreateSubscriptionMutation,
  useUpdateSubscriptionMutation,
  useDeleteSubscriptionMutation,
} = subscriptionsQuery;
