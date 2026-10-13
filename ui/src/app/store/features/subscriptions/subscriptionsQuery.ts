import { baseApi } from '@/app/store/baseApi';

export const subscriptionsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getSubscriptions: builder.query<any, void>({
      query: () => ({ url: '/subscriptions/business-subscriptions/', method: 'GET' }),
      providesTags: ['SubscriptionsAPI'],
    }),
    createSubscription: builder.mutation<any, any>({
      query: (body) => ({ url: '/subscriptions/business-subscriptions/', method: 'POST', body }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    updateSubscription: builder.mutation<any, { id: number; body: any }>({
      query: ({ id, body }) => ({ url: `/subscriptions/business-subscriptions/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    deleteSubscription: builder.mutation<any, number>({
      query: (id) => ({ url: `/subscriptions/business-subscriptions/${id}`, method: 'DELETE' }),
      invalidatesTags: ['SubscriptionsAPI'],
    }),
    getSubscription: builder.query<any, number>({
      query: (id) => ({ url: `/subscriptions/business-subscriptions/${id}`, method: 'GET' }),
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
