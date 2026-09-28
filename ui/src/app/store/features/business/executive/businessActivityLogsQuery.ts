import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const executiveBusinessActivityLogsQuery = createApi({
  reducerPath: 'executiveBusinessActivityLogsPath',

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

  tagTypes: ['ExecutiveActivityLogsAPI'],

  endpoints: (builder) => ({

    // ✅ INDEX (with filters)
    getExecutiveBusinessActivityLogs: builder.query<any, {
      page?: number;
      per_page?: number;
      user_id?: number;
      action?: string;
    }>({
      query: (params) => ({
        url: '/',
        method: 'GET',
        params,
      }),
      providesTags: ['ExecutiveActivityLogsAPI'],
    }),

    // ✅ SHOW
    getExecutiveBusinessActivityLog: builder.query<any, number>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['ExecutiveActivityLogsAPI'],
    }),

    // ❌ DESTROY
    deleteExecutiveBusinessActivityLog: builder.mutation<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ExecutiveActivityLogsAPI'],
    }),
  }),
});
export const {
  useGetExecutiveBusinessActivityLogsQuery,
  useGetExecutiveBusinessActivityLogQuery,
  useDeleteExecutiveBusinessActivityLogMutation,
} = executiveBusinessActivityLogsQuery;