import { baseApi } from '@/app/store/baseApi';

export const paymentSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getPaymentSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/payment-methods/', method: 'GET' }),
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
      query: ({ id, body }) => ({ url: `/settings/payment-methods/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PaymentSettingsAPI'],
    }),
  }),
});

export const { useGetPaymentSettingsQuery, useUpdatePaymentSettingsMutation } =
  paymentSettingsQuery;
