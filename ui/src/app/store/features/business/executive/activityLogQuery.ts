import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export interface ActivityLog {
  id: number;
  log_name: string | null;
  event: string | null;
  description: string | null;
  subject: {
    type: string | null;
    id: number | null;
    label: string | null;
  };
  causer: {
    type: string | null;
    id: number | null;
    name: string | null;
  };
  ip_address: string | null;
  user_agent: string | null;
  properties: Record<string, unknown> | null;
  changes: {
    before: Record<string, unknown>;
    after: Record<string, unknown>;
  };
  created_at: string | null;
  updated_at: string | null;
}

export interface ActivityLogMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

export interface Paginated<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: ActivityLogMeta;
}

export interface ActivityLogFilters {
  log_name?: string;
  date_from?: string;
  date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export const activityLogQuery = createApi({
  reducerPath: 'activityLogPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/dashboard/activity-logs`,
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
    getActivityLogs: builder.query<Paginated<ActivityLog>, ActivityLogFilters>({
      query: (params) => ({ url: '/', method: 'GET', params }),
      transformResponse: (response: Paginated<ActivityLog>) => ({
        data: response?.data ?? [],
        links: response?.links ?? { first: null, last: null, prev: null, next: null },
        meta: response?.meta ?? {
          current_page: 1,
          from: null,
          last_page: 1,
          per_page: 20,
          to: null,
          total: 0,
        },
      }),
      providesTags: (result) =>
        result
          ? [
              ...result.data.map(({ id }) => ({ type: 'ActivityLogAPI' as const, id })),
              'ActivityLogAPI',
            ]
          : ['ActivityLogAPI'],
    }),

    getActivityLogCategories: builder.query<{ data: string[] }, void>({
      query: () => ({ url: '/categories', method: 'GET' }),
      transformResponse: (response: { data?: string[] }) => ({ data: response?.data ?? [] }),
      providesTags: ['ActivityLogAPI'],
    }),

    getActivityLog: builder.query<{ data: ActivityLog }, number>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: (_result, _error, id) => [{ type: 'ActivityLogAPI', id }],
    }),

    deleteActivityLog: builder.mutation<void, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ActivityLogAPI'],
    }),
  }),
});

export const {
  useGetActivityLogsQuery,
  useGetActivityLogCategoriesQuery,
  useGetActivityLogQuery,
  useDeleteActivityLogMutation,
} = activityLogQuery;
