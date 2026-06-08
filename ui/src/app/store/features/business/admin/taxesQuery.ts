import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminTaxesQuery = createApi({
  reducerPath: 'adminTaxesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminTaxesAPI'],
  endpoints: (builder) => ({
    getAdminTaxes: builder.query<any, void>({
      query: () => ({ url: '/business-taxes', method: 'GET' }),
      providesTags: ['AdminTaxesAPI'],
    }),

    getAdminTax: builder.query<any, string>({
      query: (id) => ({ url: `/business-taxes/${id}`, method: 'GET' }),
      providesTags: ['AdminTaxesAPI'],
    }),

    deleteAdminTax: builder.mutation<any, string>({
      query: (id) => ({ url: `/business-taxes/${id}`, method: 'DELETE' }),
      invalidatesTags: ['AdminTaxesAPI'],
    }),

    updateAdminTax: builder.mutation<any, { id: string | number; body: any }>({
      query: ({ id, body }) => ({ url: `/business-taxes/${id}`, method: 'PUT', body }),
      invalidatesTags: ['AdminTaxesAPI'],
    }),

    // =========== Business tax payments ================
    getAdminTaxePayments: builder.query<any, void>({
      query: () => ({ url: '/business-tax-payments', method: 'GET' }),
      providesTags: ['AdminTaxesAPI'],
    }),

    getAdminTaxPayment: builder.query<any, string>({
      query: (id) => ({ url: `/business-tax-payments/${id}`, method: 'GET' }),
      providesTags: ['AdminTaxesAPI'],
    }),

    deleteAdminTaxPayment: builder.mutation<any, string>({
      query: (id) => ({
        url: `/business-tax-payments/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['AdminTaxesAPI'],
    }),

    updateAdminTaxPayment: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/business-tax-payments/${id}`, method: 'PUT', body }),
      invalidatesTags: ['AdminTaxesAPI'],
    }),
  }),
});

export const {
  useGetAdminTaxesQuery,
  useGetAdminTaxQuery,
  useUpdateAdminTaxMutation,
  useDeleteAdminTaxMutation,
  useGetAdminTaxePaymentsQuery,
  useGetAdminTaxPaymentQuery,
  useUpdateAdminTaxPaymentMutation,
  useDeleteAdminTaxPaymentMutation,
} = adminTaxesQuery;
