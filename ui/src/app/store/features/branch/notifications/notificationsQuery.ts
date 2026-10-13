// app/store/features/notifications/notificationsQuery.ts
import { baseApi } from '@/app/store/baseApi';

export interface NotificationsFilter {
  type?: string;
  is_read?: boolean;
}

export const notificationsApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getNotification: builder.query<any, string>({
      query: (id) => ({
        url: `/users/notifications/${id}`,
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
          url: qs ? `/users/notifications/?${qs}` : '/users/notifications/',
          method: 'GET',
        };
      },
      providesTags: ['Notifications'],
    }),

    getUnreadCount: builder.query<any, void>({
      query: () => ({
        url: '/users/notifications/unread-count',
        method: 'GET',
      }),
      providesTags: ['Notifications'],
    }),

    markAsRead: builder.mutation<any, string>({
      query: (id) => ({
        url: `/users/notifications/${id}/read`,
        method: 'POST',
      }),
      invalidatesTags: ['Notifications'],
    }),

    markAllAsRead: builder.mutation<any, void>({
      query: () => ({
        url: '/users/notifications/mark-all-read',
        method: 'POST',
      }),
      invalidatesTags: ['Notifications'],
    }),

    deleteNotification: builder.mutation<any, string>({
      query: (id) => ({
        url: `/users/notifications/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['Notifications'],
    }),

    clearAll: builder.mutation<any, void>({
      query: () => ({
        url: '/users/notifications/clear-all',
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
