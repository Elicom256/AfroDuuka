import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const loyaltyQuery = createApi({
  reducerPath: 'loyaltyPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/loyalty`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['LoyaltyAPI'],
  endpoints: (builder) => ({
    // Programs
    loyaltyPrograms: builder.query<any, void>({
      query: () => ({ url: '/programs', method: 'GET' }),
      providesTags: ['LoyaltyAPI'],
    }),
    createLoyaltyProgram: builder.mutation<any, any>({
      query: (body) => ({ url: '/programs', method: 'POST', body }),
      invalidatesTags: ['LoyaltyAPI'],
    }),
    deleteLoyaltyProgram: builder.mutation<any, string>({
      query: (id) => ({ url: `/programs/${id}`, method: 'DELETE' }),
      invalidatesTags: ['LoyaltyAPI'],
    }),
    // Cards
    loyaltyCards: builder.query<any, void>({
      query: () => ({ url: '/cards', method: 'GET' }),
      providesTags: ['LoyaltyAPI'],
    }),
    createLoyaltyCard: builder.mutation<any, any>({
      query: (body) => ({ url: '/cards', method: 'POST', body }),
      invalidatesTags: ['LoyaltyAPI'],
    }),
    // Rewards
    loyaltyRewards: builder.query<any, void>({
      query: () => ({ url: '/rewards', method: 'GET' }),
      providesTags: ['LoyaltyAPI'],
    }),
    createLoyaltyReward: builder.mutation<any, any>({
      query: (body) => ({ url: '/rewards', method: 'POST', body }),
      invalidatesTags: ['LoyaltyAPI'],
    }),
    deleteLoyaltyReward: builder.mutation<any, string>({
      query: (id) => ({ url: `/rewards/${id}`, method: 'DELETE' }),
      invalidatesTags: ['LoyaltyAPI'],
    }),
    // Report exports
    reportExports: builder.query<any, void>({
      query: () => ({ url: '/report-exports', method: 'GET' }),
      providesTags: ['LoyaltyAPI'],
    }),
  }),
});

export const {
  useLoyaltyProgramsQuery,
  useCreateLoyaltyProgramMutation,
  useDeleteLoyaltyProgramMutation,
  useLoyaltyCardsQuery,
  useCreateLoyaltyCardMutation,
  useLoyaltyRewardsQuery,
  useCreateLoyaltyRewardMutation,
  useDeleteLoyaltyRewardMutation,
  useReportExportsQuery,
} = loyaltyQuery;
