import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

/**
 * WhatsApp settings API client.
 * This is a demo-ready integration layer that can later be switched to a real Meta/WATI/360dialog provider.
 */
export const whatsappQuery = createApi({
  reducerPath: 'whatsappQuery',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/whatsapp`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['WhatsApp'],
  endpoints: (builder) => ({
    getWhatsAppConfig: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['WhatsApp'],
    }),
    saveWhatsAppConfig: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['WhatsApp'],
    }),
    updateWhatsAppConfig: builder.mutation<any, { id: number; data: any }>({
      query: ({ id, data }) => ({ url: `/${id}`, method: 'PUT', body: data }),
      invalidatesTags: ['WhatsApp'],
    }),
    testWhatsAppMessage: builder.mutation<any, { recipient?: string; message?: string }>({
      query: (body) => ({ url: '/test-message', method: 'POST', body }),
      invalidatesTags: ['WhatsApp'],
    }),
    getWhatsAppTemplates: builder.query<any, void>({
      query: () => ({ url: '/templates', method: 'GET' }),
      providesTags: ['WhatsApp'],
    }),
    getWhatsAppLogs: builder.query<any, void>({
      query: () => ({ url: '/logs', method: 'GET' }),
      providesTags: ['WhatsApp'],
    }),
  }),
});

export const {
  useGetWhatsAppConfigQuery,
  useSaveWhatsAppConfigMutation,
  useUpdateWhatsAppConfigMutation,
  useTestWhatsAppMessageMutation,
  useGetWhatsAppTemplatesQuery,
  useGetWhatsAppLogsQuery,
} = whatsappQuery;
