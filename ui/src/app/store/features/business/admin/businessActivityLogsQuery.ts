import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminBusinessActivityLogsQuery = createApi({
  reducerPath: 'adminBusinessActivityLogsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/activity-logs`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminActivityLogsAPI'],
  endpoints: (builder) => ({
    getAdminBusinessActivityLogs: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminActivityLogsAPI'],
    }),
  }),
});

export const { useGetAdminBusinessActivityLogsQuery } = adminBusinessActivityLogsQuery;
