import { baseApi } from '@/app/store/baseApi';

export const customerSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getCustomerSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/customers-settings/', method: 'GET' }),
      providesTags: ['CustomerSettingsAPI'],
    }),
    updateCustomerSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/settings/customers-settings/${id}`, method: 'PUT', body }),
      invalidatesTags: ['CustomerSettingsAPI'],
    }),
  }),
});

export const { useGetCustomerSettingsQuery, useUpdateCustomerSettingsMutation } = customerSettingsQuery;
