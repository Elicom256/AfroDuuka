import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const reportsSettingsQuery = createApi({
  reducerPath: 'reportsSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/reports-settings`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ReportsSettingsAPI'],
  endpoints: (builder) => ({
    getReportsSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ReportsSettingsAPI'],
    }),
    updateReportsSettings: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'PUT', body }),
      invalidatesTags: ['ReportsSettingsAPI'],
    }),
  }),
});

export const { useGetReportsSettingsQuery, useUpdateReportsSettingsMutation } = reportsSettingsQuery;
