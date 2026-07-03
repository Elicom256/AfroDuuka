import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const subscriptionsQuery = createApi({
  reducerPath: 'subscriptionsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/subscriptions`,
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
  }),
});

export const { useGetSubscriptionsQuery } = subscriptionsQuery;
