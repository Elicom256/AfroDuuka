// app/store/features/notifications/notificationsQuery.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export interface NotificationsFilter {
  type?: string;
  is_read?: boolean;
}

export const notificationsApi = createApi({
  reducerPath: 'notificationsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/users/notifications`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        // console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['Notifications'],
  endpoints: (builder) => ({
    getNotification: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['Notifications'],
    }),

    getNotifications: builder.query<any, NotificationsFilter | void>({
      query: (filters) => {
        const params = new URLSearchParams();
        if (filters?.type) params.set('type', filters.type);
        if (filters?.is_read !== undefined) params.set('is_read', String(filters.is_read));
        const qs = params.toString();
        return {
          url: qs ? `/?${qs}` : '/',
          method: 'GET',
        };
      },
      providesTags: ['Notifications'],
    }),

    getUnreadCount: builder.query<any, void>({
      query: () => ({
        url: '/unread-count',
        method: 'GET',
      }),
      providesTags: ['Notifications'],
    }),

    markAsRead: builder.mutation<any, string>({
      query: (id) => ({
        url: `/${id}/read`,
        method: 'POST',
      }),
      invalidatesTags: ['Notifications'],
    }),

    markAllAsRead: builder.mutation<any, void>({
      query: () => ({
        url: '/mark-all-read',
        method: 'POST',
      }),
      invalidatesTags: ['Notifications'],
    }),

    deleteNotification: builder.mutation<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['Notifications'],
    }),

    clearAll: builder.mutation<any, void>({
      query: () => ({
        url: '/clear-all',
        method: 'POST',
      }),
      invalidatesTags: ['Notifications'],
    }),
  }),
});

export const {
  useGetNotificationQuery,
  useGetNotificationsQuery,
  useGetUnreadCountQuery,
  useMarkAsReadMutation,
  useMarkAllAsReadMutation,
  useDeleteNotificationMutation,
  useClearAllMutation,
} = notificationsApi;
