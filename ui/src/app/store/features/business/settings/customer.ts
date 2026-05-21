import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const customerSettingsQuery = createApi({
  reducerPath: 'customerSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/customer-settings`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['CustomerSettingsAPI'],
  endpoints: (builder) => ({
    getCustomerSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['CustomerSettingsAPI'],
    }),
    updateCustomerSettings: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'PUT', body }),
      invalidatesTags: ['CustomerSettingsAPI'],
    }),
  }),
});

export const { useGetCustomerSettingsQuery, useUpdateCustomerSettingsMutation } = customerSettingsQuery;
