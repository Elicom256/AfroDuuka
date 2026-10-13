import { baseApi } from '@/app/store/baseApi';

/**
 * WhatsApp settings API client.
 * This is a demo-ready integration layer that can later be switched to a real Meta/WATI/360dialog provider.
 */
export const whatsappQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getWhatsAppConfig: builder.query<any, void>({
      query: () => ({ url: '/whatsapp/', method: 'GET' }),
      providesTags: ['WhatsApp'],
    }),
    saveWhatsAppConfig: builder.mutation<any, any>({
      query: (body) => ({ url: '/whatsapp/', method: 'POST', body }),
      invalidatesTags: ['WhatsApp'],
    }),
    updateWhatsAppConfig: builder.mutation<any, { id: number; data: any }>({
      query: ({ id, data }) => ({ url: `/whatsapp/${id}`, method: 'PUT', body: data }),
      invalidatesTags: ['WhatsApp'],
    }),
    testWhatsAppMessage: builder.mutation<any, { recipient?: string; message?: string }>({
      query: (body) => ({ url: '/whatsapp/test-message', method: 'POST', body }),
      invalidatesTags: ['WhatsApp'],
    }),
    getWhatsAppTemplates: builder.query<any, void>({
      query: () => ({ url: '/whatsapp/templates', method: 'GET' }),
      providesTags: ['WhatsApp'],
    }),
    getWhatsAppLogs: builder.query<any, void>({
      query: () => ({ url: '/whatsapp/logs', method: 'GET' }),
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
