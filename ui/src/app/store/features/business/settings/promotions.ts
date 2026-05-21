import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const promotionsSettingsQuery = createApi({
  reducerPath: 'promotionsSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/promotions-settings`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PromotionsSettingsAPI'],
  endpoints: (builder) => ({
    getPromotionsSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PromotionsSettingsAPI'],
    }),
    updatePromotionsSettings: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'PUT', body }),
      invalidatesTags: ['PromotionsSettingsAPI'],
    }),
  }),
});

export const { useGetPromotionsSettingsQuery, useUpdatePromotionsSettingsMutation } = promotionsSettingsQuery;
