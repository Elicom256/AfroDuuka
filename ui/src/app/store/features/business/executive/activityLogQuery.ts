import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const activityLogQuery = createApi({
  reducerPath: 'activityLogPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/activity-logs`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ActivityLogAPI'],
  endpoints: (builder) => ({
    getActivityLogs: builder.query<any, any>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.log_name) searchParams.set('log_name', params.log_name);
        if (params?.causer_id) searchParams.set('causer_id', params.causer_id);
        if (params?.subject_type) searchParams.set('subject_type', params.subject_type);
        if (params?.date_from) searchParams.set('date_from', params.date_from);
        if (params?.date_to) searchParams.set('date_to', params.date_to);
        if (params?.per_page) searchParams.set('per_page', params.per_page);
        const qs = searchParams.toString();
        return { url: qs ? `/?${qs}` : '/', method: 'GET' };
      },
      providesTags: ['ActivityLogAPI'],
    }),
    getActivityLog: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ActivityLogAPI'],
    }),
    deleteActivityLog: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ActivityLogAPI'],
    }),
  }),
});

export const {
  useGetActivityLogsQuery,
  useGetActivityLogQuery,
  useDeleteActivityLogMutation,
} = activityLogQuery;
