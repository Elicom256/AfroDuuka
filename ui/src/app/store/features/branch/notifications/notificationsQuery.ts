import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchNotificationsQuery = createApi({
  reducerPath: 'branchNotificationsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/notifications`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchNotificationsAPI'],
  endpoints: (builder) => ({
    branchNotifications: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchNotificationsAPI'],
    }),
  }),
});

export const { useBranchNotificationsQuery } = branchNotificationsQuery;
