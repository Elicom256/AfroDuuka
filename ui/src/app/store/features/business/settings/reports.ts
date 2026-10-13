import { baseApi } from '@/app/store/baseApi';

export const reportsSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getReportsSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/reports-settings/', method: 'GET' }),
      providesTags: ['ReportsSettingsAPI'],
    }),
    updateReportsSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/settings/reports-settings/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ReportsSettingsAPI'],
    }),
  }),
});

export const { useGetReportsSettingsQuery, useUpdateReportsSettingsMutation } = reportsSettingsQuery;
