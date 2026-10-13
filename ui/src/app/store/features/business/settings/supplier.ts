import { baseApi } from '@/app/store/baseApi';

export const supplierSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getSupplierSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/suppliers-settings/', method: 'GET' }),
      providesTags: ['SupplierSettingsAPI'],
    }),
    updateSupplierSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/settings/suppliers-settings/${id}`, method: 'PUT', body }),
      invalidatesTags: ['SupplierSettingsAPI'],
    }),
  }),
});

export const { useGetSupplierSettingsQuery, useUpdateSupplierSettingsMutation } = supplierSettingsQuery;
