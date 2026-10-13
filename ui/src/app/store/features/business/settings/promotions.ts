import { baseApi } from '@/app/store/baseApi';

export const promotionsSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getPromotionsSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/promotions-settings/', method: 'GET' }),
      providesTags: ['PromotionsSettingsAPI'],
    }),
    updatePromotionsSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/settings/promotions-settings/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PromotionsSettingsAPI'],
    }),
  }),
});

export const { useGetPromotionsSettingsQuery, useUpdatePromotionsSettingsMutation } = promotionsSettingsQuery;
