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

    // ✅ INDEX (with filters)
    getAdminBusinessActivityLogs: builder.query<any, {
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
      providesTags: ['AdminActivityLogsAPI'],
    }),

    // ✅ SHOW
    getAdminBusinessActivityLog: builder.query<any, number>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['AdminActivityLogsAPI'],
    }),

    // ❌ DESTROY
    deleteAdminBusinessActivityLog: builder.mutation<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['AdminActivityLogsAPI'],
    }),
  }),
});
export const {
  useGetAdminBusinessActivityLogsQuery,
  useGetAdminBusinessActivityLogQuery,
  useDeleteAdminBusinessActivityLogMutation,
} = adminBusinessActivityLogsQuery;