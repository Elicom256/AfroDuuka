import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const supplierSettingsQuery = createApi({
  reducerPath: 'supplierSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/suppliers-settings`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SupplierSettingsAPI'],
  endpoints: (builder) => ({
    getSupplierSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['SupplierSettingsAPI'],
    }),
    updateSupplierSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SupplierSettingsAPI'],
    }),
  }),
});

export const { useGetSupplierSettingsQuery, useUpdateSupplierSettingsMutation } = supplierSettingsQuery;
