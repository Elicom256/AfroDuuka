import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const paymentSettingsQuery = createApi({
  reducerPath: 'paymentSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/payment-methods`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PaymentSettingsAPI'],
  endpoints: (builder) => ({
    getPaymentSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PaymentSettingsAPI'],
    }),
    // allowedPaymentMethods: builder.query<any, void>({
    //   query: () => ({
    //     url: '/allowed',
    //     method: 'GET',
    //   }),
    //   providesTags: ['PaymentSettingsAPI'],
    // }),
    updatePaymentSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PaymentSettingsAPI'],
    }),
  }),
});

export const { useGetPaymentSettingsQuery, useUpdatePaymentSettingsMutation } =
  paymentSettingsQuery;
